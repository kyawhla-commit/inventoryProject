<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;
use App\Models\RawMaterial;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\StockMovement;

class RawMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rawMaterials = RawMaterial::with('supplier')->latest()->paginate(10);
        return view('raw-materials.index', compact('rawMaterials'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::all();
        return view('raw-materials.create', compact('suppliers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'minimum_stock_level' => 'required|numeric|min:0',
        ]);

        RawMaterial::create($request->all());

        return redirect()->route('raw-materials.index')
                         ->with('success', 'Raw material created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RawMaterial $rawMaterial)
    {
        $suppliers = Supplier::all();
        return view('raw-materials.edit', compact('rawMaterial', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RawMaterial $rawMaterial)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'minimum_stock_level' => 'required|numeric|min:0',
        ]);

        $rawMaterial->update($request->all());

        return redirect()->route('raw-materials.index')
            ->with('success', 'Raw material updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function lowStock()
    {
        $lowStockMaterials = RawMaterial::whereColumn('quantity', '<=', 'minimum_stock_level')->get();
        return view('raw-materials.low-stock', compact('lowStockMaterials'));
    }

    public function updateStockFromPurchase(Purchase $purchase)
    {
        try {
            DB::beginTransaction();
    
            foreach ($purchase->purchaseItems as $item) {
                if ($item->raw_material_id) {
                    $rawMaterial = RawMaterial::findOrFail($item->raw_material_id);
                    $rawMaterial->quantity += $item->quantity;
                    $rawMaterial->cost_per_unit = $item->unit_price; // Update cost per unit with latest purchase price
                    $rawMaterial->last_purchase_date = $purchase->purchase_date;
                    $rawMaterial->last_purchase_price = $item->unit_price;
                    $rawMaterial->save();
    
                    // Record stock movement
                    StockMovement::create([
                        'raw_material_id' => $rawMaterial->id,
                        'movement_type' => 'purchase',
                        'quantity' => $item->quantity,
                        'reference_id' => $purchase->id,
                        'reference_type' => 'App\Models\Purchase',
                        'unit_price' => $item->unit_price,
                        'total_amount' => $item->total_amount,
                        'notes' => "Stock added from purchase #{$purchase->purchase_number}"
                    ]);
                }
            }
    
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
