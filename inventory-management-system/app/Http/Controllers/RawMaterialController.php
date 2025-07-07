<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RawMaterial;
use App\Models\Supplier;

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
}
