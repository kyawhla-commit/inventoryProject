<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices
     */
    public function index(Request $request)
    {
        try {
            $query = Invoice::with(['customer', 'creator']);

            // Filter by status
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            // Filter by date range
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->forPeriod($request->start_date, $request->end_date);
            }

            // Filter by customer
            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            // Check for overdue invoices
            if ($request->get('show_overdue')) {
                $query->overdue();
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($customerQuery) use ($search) {
                          $customerQuery->where('name', 'like', "%{$search}%")
                                      ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            $perPage = $request->get('per_page', 15);
            $invoices = $query->orderBy('invoice_date', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $invoices->items(),
                'pagination' => [
                    'current_page' => $invoices->currentPage(),
                    'last_page' => $invoices->lastPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created invoice
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'nullable|exists:customers,id',
                'invoice_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:invoice_date',
                'tax_rate' => 'nullable|numeric|min:0|max:100',
                'discount_rate' => 'nullable|numeric|min:0|max:100',
                'notes' => 'nullable|string|max:1000',
                'payment_terms' => 'nullable|string|max:255',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'nullable|exists:products,id',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit' => 'nullable|string|max:50',
                'items.*.unit_price' => 'required|numeric|min:0',
                'voucher_id' => 'nullable|exists:vouchers,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $invoice = Invoice::create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'sale_id' => $request->sale_id,
                'customer_id' => $request->customer_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'tax_rate' => $request->tax_rate ?? 0,
                'discount_rate' => $request->discount_rate ?? 0,
                'notes' => $request->notes,
                'payment_terms' => $request->payment_terms ?? 'Net 30',
                'created_by' => Auth::id(),
                'subtotal' => 0,
                'total_amount' => 0,
                'voucher_id' => $request->voucher_id,
            ]);

            // Create invoice items
            foreach ($request->items as $itemData) {
                $item = new InvoiceItem($itemData);
                $item->calculateTotal();
                $invoice->items()->save($item);
            }

            // Calculate totals
            $invoice->calculateTotals();
            $invoice->save();

            // Load relationships for response
            $invoice->load(['customer', 'items.product', 'creator', 'voucher']);

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified invoice
     */
    public function show($id)
    {
        try {
            $invoice = Invoice::with(['customer', 'items.product', 'creator', 'sale', 'voucher'])
                             ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified invoice
     */
    public function update(Request $request, $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);

            if ($invoice->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit paid invoices'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'customer_id' => 'nullable|exists:customers,id',
                'invoice_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:invoice_date',
                'tax_rate' => 'nullable|numeric|min:0|max:100',
                'discount_rate' => 'nullable|numeric|min:0|max:100',
                'notes' => 'nullable|string|max:1000',
                'payment_terms' => 'nullable|string|max:255',
                'status' => 'required|in:draft,sent,paid,overdue,cancelled',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'nullable|exists:products,id',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit' => 'nullable|string|max:50',
                'items.*.unit_price' => 'required|numeric|min:0',
                'voucher_id' => 'nullable|exists:vouchers,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $invoice->update([
                'customer_id' => $request->customer_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'tax_rate' => $request->tax_rate ?? 0,
                'discount_rate' => $request->discount_rate ?? 0,
                'notes' => $request->notes,
                'payment_terms' => $request->payment_terms ?? 'Net 30',
                'status' => $request->status,
                'voucher_id' => $request->voucher_id,
            ]);

            // Delete existing items and create new ones
            $invoice->items()->delete();

            foreach ($request->items as $itemData) {
                $item = new InvoiceItem($itemData);
                $item->calculateTotal();
                $invoice->items()->save($item);
            }

            // Recalculate totals
            $invoice->calculateTotals();
            $invoice->save();

            // Load relationships for response
            $invoice->load(['customer', 'items.product', 'creator', 'voucher']);

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified invoice
     */
    public function destroy($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);

            if ($invoice->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete paid invoices'
                ], 422);
            }

            $invoice->delete();

            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create invoice from sale
     */
    public function createFromSale(Request $request, $saleId)
    {
        try {
            $sale = Sale::findOrFail($saleId);

            // Check if invoice already exists for this sale
            if ($sale->invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice already exists for this sale',
                    'data' => $sale->invoice
                ], 422);
            }

            $invoice = (new Invoice())->createFromSale($sale);

            return response()->json([
                'success' => true,
                'message' => 'Invoice created from sale successfully',
                'data' => $invoice
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice from sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create invoice from order
     */
    public function createFromOrder(Request $request, $orderId)
    {
        try {
            $order = Order::findOrFail($orderId);

            // Check if invoice already exists for this order
            if ($order->invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice already exists for this order',
                    'data' => $order->invoice
                ], 422);
            }

            $invoice = (new Invoice())->createFromOrder($order);

            return response()->json([
                'success' => true,
                'message' => 'Invoice created from order successfully',
                'data' => $invoice
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice from order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            $invoice->update(['status' => 'paid']);

            return response()->json([
                'success' => true,
                'message' => 'Invoice marked as paid successfully',
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark invoice as paid',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark invoice as sent
     */
    public function markAsSent($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            $invoice->update(['status' => 'sent']);

            return response()->json([
                'success' => true,
                'message' => 'Invoice marked as sent successfully',
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark invoice as sent',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invoice statistics
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_invoices' => Invoice::count(),
                'draft_invoices' => Invoice::where('status', 'draft')->count(),
                'sent_invoices' => Invoice::where('status', 'sent')->count(),
                'paid_invoices' => Invoice::where('status', 'paid')->count(),
                'overdue_invoices' => Invoice::overdue()->count(),
                'total_amount' => Invoice::sum('total_amount'),
                'paid_amount' => Invoice::where('status', 'paid')->sum('total_amount'),
                'pending_amount' => Invoice::whereIn('status', ['draft', 'sent', 'overdue'])->sum('total_amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customers for invoice creation
     */
    public function getCustomers()
    {
        try {
            $customers = Customer::select('id', 'name', 'email', 'phone')->get();

            return response()->json([
                'success' => true,
                'data' => $customers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch customers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products for invoice creation
     */
    public function getProducts()
    {
        try {
            $products = Product::where('quantity', '>', 0)
                              ->select('id', 'name', 'price', 'quantity', 'unit')
                              ->get();

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}