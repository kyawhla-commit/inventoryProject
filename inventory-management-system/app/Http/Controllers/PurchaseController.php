<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('supplier')->latest()->paginate(10);
        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $products = Product::all();
        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $totalAmount = 0;
            foreach ($request->products as $productData) {
                $totalAmount += $productData['quantity'] * $productData['cost'];
            }

            $purchase = Purchase::create([
                'supplier_id' => $request->supplier_id,
                'purchase_date' => $request->purchase_date,
                'total_amount' => $totalAmount,
            ]);

            foreach ($request->products as $productData) {
                $purchase->items()->create([
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'cost' => $productData['cost'],
                ]);

                // Update product stock
                $product = Product::find($productData['product_id']);
                $product->quantity += $productData['quantity'];
                $product->save();
            }
        });

        return redirect()->route('purchases.index')->with('success', 'Purchase created successfully.');
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'items.product');
        return view('purchases.show', compact('purchase'));
    }
}
