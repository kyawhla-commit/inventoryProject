<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Services\RawMaterialService;
use Illuminate\Support\Facades\DB;

class RawMaterialController extends Controller
{
    protected RawMaterialService $rawMaterialService;

    public function __construct(RawMaterialService $rawMaterialService)
    {
        $this->rawMaterialService = $rawMaterialService;
    }

    /**
     * Display a listing of raw materials
     */
    public function index(Request $request)
    {
        $rawMaterials = $this->rawMaterialService->search([
            'search' => $request->search,
            'supplier_id' => $request->supplier_id,
            'low_stock' => $request->low_stock,
            'sort_by' => $request->sort_by ?? 'name',
            'sort_dir' => $request->sort_dir ?? 'asc',
            'per_page' => 15,
        ]);

        $suppliers = Supplier::orderBy('name')->get();
        
        // Get dashboard stats
        $stats = $this->rawMaterialService->getDashboardStats();

        return view('raw-materials.index', compact('rawMaterials', 'suppliers', 'stats'));
    }

    /**
     * Show the form for creating a new raw material
     */
    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $units = ['kg', 'g', 'lb', 'oz', 'L', 'mL', 'pcs', 'm', 'cm', 'ft', 'in'];

        return view('raw-materials.create', compact('suppliers', 'units'));
    }

    /**
     * Store a newly created raw material
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'minimum_stock_level' => 'required|numeric|min:0',
        ]);

        try {
            $material = RawMaterial::create($validated);

            return redirect()
                ->route('raw-materials.show', $material)
                ->with('success', __('Raw material created successfully.'));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Failed to create raw material: ') . $e->getMessage());
        }
    }

    /**
     * Display the specified raw material
     */
    public function show(RawMaterial $rawMaterial)
    {
        $rawMaterial->load(['supplier', 'products']);
        
        // Get usage statistics
        $usageStats = $this->rawMaterialService->getUsageStatistics($rawMaterial);
        
        // Get reorder analysis
        $reorderAnalysis = $this->rawMaterialService->calculateReorderPoint($rawMaterial);
        
        // Get purchase history
        $purchaseHistory = $this->rawMaterialService->getPurchaseHistory($rawMaterial, 10);
        
        // Get stock movements
        $stockMovements = $this->rawMaterialService->getStockMovements($rawMaterial, 20);
        
        // Get price trend
        $priceTrend = $this->rawMaterialService->getPriceTrend($rawMaterial, 6);

        return view('raw-materials.show', compact(
            'rawMaterial',
            'usageStats',
            'reorderAnalysis',
            'purchaseHistory',
            'stockMovements',
            'priceTrend'
        ));
    }

    /**
     * Show the form for editing the specified raw material
     */
    public function edit(RawMaterial $rawMaterial)
    {
        $suppliers = Supplier::orderBy('name')->get();
        $units = ['kg', 'g', 'lb', 'oz', 'L', 'mL', 'pcs', 'm', 'cm', 'ft', 'in'];

        return view('raw-materials.edit', compact('rawMaterial', 'suppliers', 'units'));
    }

    /**
     * Update the specified raw material
     */
    public function update(Request $request, RawMaterial $rawMaterial)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'minimum_stock_level' => 'required|numeric|min:0',
        ]);

        try {
            $rawMaterial->update($validated);

            return redirect()
                ->route('raw-materials.show', $rawMaterial)
                ->with('success', __('Raw material updated successfully.'));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Failed to update raw material: ') . $e->getMessage());
        }
    }

    /**
     * Remove the specified raw material
     */
    public function destroy(RawMaterial $rawMaterial)
    {
        // Check if material is used in any products or recipes
        if ($rawMaterial->products()->count() > 0) {
            return redirect()
                ->route('raw-materials.index')
                ->with('error', __('Cannot delete raw material that is used in products.'));
        }

        if ($rawMaterial->recipeItems()->count() > 0) {
            return redirect()
                ->route('raw-materials.index')
                ->with('error', __('Cannot delete raw material that is used in recipes.'));
        }

        $rawMaterial->delete();

        return redirect()
            ->route('raw-materials.index')
            ->with('success', __('Raw material deleted successfully.'));
    }

    /**
     * Display low stock materials
     */
    public function lowStock()
    {
        $lowStockMaterials = $this->rawMaterialService->getMaterialsNeedingReorder();
        
        // Group by supplier for easy ordering
        $bySupplier = $lowStockMaterials->groupBy('supplier_id');

        return view('raw-materials.low-stock', compact('lowStockMaterials', 'bySupplier'));
    }

    /**
     * Adjust stock quantity
     */
    public function adjustStock(Request $request, RawMaterial $rawMaterial)
    {
        $validated = $request->validate([
            'adjustment' => 'required|numeric',
            'reason' => 'required|string|max:255',
            'type' => 'required|in:adjustment,damage,return,correction',
        ]);

        try {
            $this->rawMaterialService->adjustStock(
                $rawMaterial,
                $validated['adjustment'],
                $validated['reason'],
                $validated['type']
            );

            return redirect()
                ->route('raw-materials.show', $rawMaterial)
                ->with('success', __('Stock adjusted successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get purchase history (AJAX)
     */
    public function purchaseHistory(RawMaterial $rawMaterial)
    {
        $history = $this->rawMaterialService->getPurchaseHistory($rawMaterial, 20);
        
        return response()->json($history);
    }

    /**
     * Get usage statistics (AJAX)
     */
    public function usageStats(Request $request, RawMaterial $rawMaterial)
    {
        $startDate = $request->start_date ? \Carbon\Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date) : null;

        $stats = $this->rawMaterialService->getUsageStatistics($rawMaterial, $startDate, $endDate);
        
        return response()->json($stats);
    }

    /**
     * Export raw materials to CSV
     */
    public function export(Request $request)
    {
        $materials = RawMaterial::with('supplier')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->low_stock, fn($q) => $q->lowStock())
            ->orderBy('name')
            ->get();

        $filename = 'raw_materials_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($materials) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'SKU', 'Name', 'Category', 'Supplier', 'Quantity', 'Unit',
                'Cost/Unit', 'Stock Value', 'Min Stock', 'Status', 'Location'
            ]);

            foreach ($materials as $material) {
                fputcsv($file, [
                    $material->sku ?? '-',
                    $material->name,
                    $material->category ?? '-',
                    $material->supplier->name ?? '-',
                    $material->quantity,
                    $material->unit,
                    $material->cost_per_unit,
                    $material->stock_value,
                    $material->minimum_stock_level,
                    $material->status,
                    $material->location ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
