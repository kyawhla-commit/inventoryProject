<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::latest()->paginate(10);
        return view('sales.index', compact('sales'))
            ->with('i', (request()->input('page', 1) - 1) * 10);
    }

    public function create()
    {
        $products = Product::all();
        return view('sales.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_date' => 'required|date',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // First, check for sufficient stock for all items
            foreach ($request->products as $productData) {
                $product = Product::find($productData['product_id']);
                if ($product->quantity < $productData['quantity']) {
                    throw new \Exception('Not enough stock for product: ' . $product->name . '. Available: ' . $product->quantity . ', Requested: ' . $productData['quantity']);
                }
            }

            $sale = Sale::create([
                'sale_date' => $request->sale_date,
                'total_amount' => 0, // Will be updated later
            ]);

            $totalAmount = 0;

            foreach ($request->products as $productData) {
                $product = Product::find($productData['product_id']);
                $quantity = $productData['quantity'];
                $unitPrice = $productData['unit_price'];
                $itemTotal = $quantity * $unitPrice;
                $totalAmount += $itemTotal;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                ]);

                // Update product stock
                $product->quantity -= $quantity;
                $product->save();
            }

            $sale->total_amount = $totalAmount;
            $sale->save();

            DB::commit();

            return redirect()->route('sales.index')
                ->with('success', 'Sale created successfully and stock updated.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to create sale. ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Sale $sale)
    {
        $sale->load('items.product');
        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        abort(404);
    }

    public function update(Request $request, Sale $sale)
    {
        abort(404);
    }

    public function destroy(Sale $sale)
    {
        DB::beginTransaction();
        try {
            foreach($sale->items as $item) {
                $product = $item->product;
                $product->quantity += $item->quantity;
                $product->save();
            }
            $sale->delete();
            DB::commit();
            return redirect()->route('sales.index')
                ->with('success', 'Sale deleted successfully and stock restored.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('sales.index')
                ->with('error', 'Failed to delete sale. ' . $e->getMessage());
        }
    }
}
