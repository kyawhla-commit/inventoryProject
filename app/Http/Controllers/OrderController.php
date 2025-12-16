<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with('customer');

        if ($search = $request->input('q')) {
            $query->where('id', $search)
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        return view('orders.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::with('category')
            ->orderBy('name')
            ->get();
        
        return view('orders.create', compact('customers', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date'  => 'required|date',
            'products'               => 'required|array',
            'products.*.product_id'  => 'required|exists:products,id',
            'products.*.quantity'    => 'required|integer|min:1',
            'products.*.price'       => 'required|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($request) {
            $totalAmount = 0;
            foreach ($request->products as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            $order = Order::create([
                'customer_id'   => $request->customer_id,
                'order_date'    => $request->order_date,
                'total_amount'  => $totalAmount,
                'status'        => 'pending',
            ]);

            foreach ($request->products as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'],
                ]);

                // deduct product stock
                $product = \App\Models\Product::find($item['product_id']);
                if ($product) {
                    $product->decrement('quantity', $item['quantity']);
                }
            }

            return $order;
        });

        // Automatically create invoice for the order
        if ($order) {
            $invoice = (new \App\Models\Invoice())->createFromOrder($order);
        }

        return redirect()->route('orders.show', $order)->with('success', 'Order and invoice created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load(['customer', 'items.product', 'invoice', 'sales', 'purchases']);
        // Convert date strings to Carbon instances for Blade formatting
        if ($order->invoice) {
            $order->invoice->invoice_date = \Carbon\Carbon::parse($order->invoice->invoice_date);
            $order->invoice->due_date = \Carbon\Carbon::parse($order->invoice->due_date);
        }
        foreach ($order->sales as $sale) {
            $sale->sale_date = \Carbon\Carbon::parse($sale->sale_date);
        }
        foreach ($order->purchases as $purchase) {
            $purchase->purchase_date = \Carbon\Carbon::parse($purchase->purchase_date);
        }
        return view('orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        if ($order->status === 'completed') {
            return redirect()->back()->with('error', 'Completed orders cannot be edited.');
        }
        $order->load(['items.product']);
        $products = \App\Models\Product::all();
        return view('orders.edit', compact('order', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|string|in:pending,confirmed,processing,shipped,completed,cancelled',
            'items' => 'sometimes|string',
            'notes' => 'nullable|string',
        ]);

        Log::info('Update order request:', $request->all());

        return DB::transaction(function () use ($request, $order) {
            $items = [];
            if (!empty($request->items)) {
                $items = json_decode($request->items, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Failed to decode items JSON:', [
                        'error' => json_last_error_msg(),
                        'items' => $request->items
                    ]);
                    throw new \Exception('Invalid items data');
                }
            }
            
            $existingItemIds = [];
            $totalAmount = 0;
            Log::info('Processing items:', ['items' => $items]);

            // Update or create order items
            foreach ($items as $itemData) {
                $itemId = $itemData['id'] ?? null;
                $quantity = (int)($itemData['quantity'] ?? 1);
                $price = (float)($itemData['price'] ?? 0);
                
                // Calculate item total
                $totalAmount += $quantity * $price;

                if (str_starts_with($itemId, 'new-')) {
                    // New item
                    $order->items()->create([
                        'product_id' => $itemData['product_id'],
                        'quantity' => $quantity,
                        'price' => $price,
                    ]);
                } else {
                    // Existing item
                    $item = $order->items()->find($itemId);
                    if ($item) {
                        $originalQuantity = $item->quantity;
                        $item->update([
                            'quantity' => $quantity,
                            'price' => $price,
                        ]);
                        $existingItemIds[] = $itemId;

                        // Update product stock if quantity changed
                        if ($item->product && $originalQuantity != $quantity) {
                            $quantityDiff = $originalQuantity - $quantity;
                            if ($quantityDiff > 0) {
                                $item->product->increment('quantity', $quantityDiff);
                            } else {
                                $item->product->decrement('quantity', abs($quantityDiff));
                            }
                        }
                    }
                }
            }

            // Remove items not in the request
            $order->items()->whereNotIn('id', $existingItemIds)->delete();

            // Update order totals and notes
            $order->update([
                'status' => $request->status,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
            ]);

            return redirect()->route('orders.show', $order)
                ->with('success', 'Order updated successfully.');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Confirm order - validates stock and reserves it
     */
    public function confirm(Order $order)
    {
        try {
            $this->orderService->confirmOrder($order);
            
            return redirect()->route('orders.show', $order)
                ->with('success', __('Order confirmed successfully. Stock has been reserved.'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('Failed to confirm order: ') . $e->getMessage());
        }
    }

    /**
     * Cancel order - restores stock if reserved
     */
    public function cancel(Request $request, Order $order)
    {
        try {
            $reason = $request->input('reason');
            $this->orderService->cancelOrder($order, $reason);
            
            return redirect()->route('orders.show', $order)
                ->with('success', __('Order cancelled successfully. Stock has been restored.'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('Failed to cancel order: ') . $e->getMessage());
        }
    }

    /**
     * Convert order to sale
     */
    public function convertToSale(Order $order)
    {
        try {
            $sale = $this->orderService->convertToSale($order);
            
            return redirect()->route('sales.show', $sale)
                ->with('success', __('Order converted to sale successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('Failed to convert order to sale: ') . $e->getMessage());
        }
    }

    /**
     * Create invoice for order
     */
    public function createInvoice(Order $order)
    {
        try {
            $invoice = $this->orderService->createInvoice($order);
            
            return redirect()->route('invoices.show', $invoice)
                ->with('success', __('Invoice created successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('Failed to create invoice: ') . $e->getMessage());
        }
    }

    /**
     * Show form to create purchase order from customer order
     */
    public function createPurchaseForm(Order $order)
    {
        $suppliers = \App\Models\Supplier::all();
        $products = \App\Models\Product::all();
        
        return view('orders.create-purchase', compact('order', 'suppliers', 'products'));
    }

    /**
     * Create purchase order from customer order
     */
    public function createPurchase(Request $request, Order $order)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.description' => 'required|string|max:255',
        ]);

        try {
            $purchase = $order->createPurchaseOrder($request->supplier_id, $request->items);
            
            return redirect()->route('purchases.show', $purchase)
                ->with('success', 'Purchase order created successfully from customer order.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create purchase order: ' . $e->getMessage());
        }
    }
}
