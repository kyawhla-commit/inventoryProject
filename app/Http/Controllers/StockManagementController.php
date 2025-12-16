<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\StockMovement;
use App\Services\ProductionService;
use App\Services\RawMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockManagementController extends Controller
{
    protected ProductionService $productionService;
    protected RawMaterialService $rawMaterialService;

    public function __construct(ProductionService $productionService, RawMaterialService $rawMaterialService)
    {
        $this->productionService = $productionService;
        $this->rawMaterialService = $rawMaterialService;
    }

    /**
     * Stock management dashboard
     */
    public function index()
    {
        // Raw materials summary
        $rawMaterials = RawMaterial::with('supplier')->get();
        $rawMaterialStats = [
            'total' => $rawMaterials->count(),
            'total_value' => $rawMaterials->sum(fn($m) => $m->quantity * $m->cost_per_unit),
            'low_stock' => $rawMaterials->filter(fn($m) => $m->isLowStock())->count(),
            'out_of_stock' => $rawMaterials->filter(fn($m) => $m->quantity <= 0)->count(),
        ];

        // Products summary
        $products = Product::all();
        $productStats = [
            'total' => $products->count(),
            'total_value' => $products->sum(fn($p) => $p->quantity * ($p->cost ?? 0)),
            'low_stock' => $products->filter(fn($p) => isset($p->minimum_stock_level) && $p->quantity <= $p->minimum_stock_level)->count(),
            'out_of_stock' => $products->filter(fn($p) => $p->quantity <= 0)->count(),
        ];

        // Recent stock movements
        $recentMovements = StockMovement::with(['product', 'rawMaterial', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Low stock alerts
        $lowStockMaterials = $rawMaterials->filter(fn($m) => $m->isLowStock())->take(10);
        $lowStockProducts = $products->filter(fn($p) => isset($p->minimum_stock_level) && $p->quantity <= $p->minimum_stock_level)->take(10);

        return view('stock-management.index', compact(
            'rawMaterialStats',
            'productStats',
            'recentMovements',
            'lowStockMaterials',
            'lowStockProducts'
        ));
    }

    /**
     * Show add stock form for raw materials
     */
    public function addRawMaterialForm()
    {
        $rawMaterials = RawMaterial::orderBy('name')->get();
        return view('stock-management.add-raw-material', compact('rawMaterials'));
    }

    /**
     * Add stock to raw material
     */
    public function addRawMaterialStock(Request $request)
    {
        $validated = $request->validate([
            'raw_material_id' => 'required|exists:raw_materials,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_price' => 'required|numeric|min:0',
            'type' => 'required|in:purchase,return,adjustment,initial',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $material = RawMaterial::findOrFail($validated['raw_material_id']);
            
            $this->productionService->addRawMaterialStock(
                $material,
                $validated['quantity'],
                $validated['unit_price'],
                $validated['type'],
                $validated['notes']
            );

            return redirect()
                ->route('stock-management.index')
                ->with('success', __('Stock added successfully. :name now has :qty :unit', [
                    'name' => $material->name,
                    'qty' => number_format($material->fresh()->quantity, 2),
                    'unit' => $material->unit,
                ]));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Failed to add stock: ') . $e->getMessage());
        }
    }

    /**
     * Show add stock form for products
     */
    public function addProductForm()
    {
        $products = Product::orderBy('name')->get();
        return view('stock-management.add-product', compact('products'));
    }

    /**
     * Add stock to product
     */
    public function addProductStock(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'cost' => 'nullable|numeric|min:0',
            'type' => 'required|in:production,return,adjustment,initial',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $product = Product::findOrFail($validated['product_id']);
            
            $this->productionService->addProductStock(
                $product,
                $validated['quantity'],
                $validated['cost'],
                $validated['type'],
                $validated['notes']
            );

            return redirect()
                ->route('stock-management.index')
                ->with('success', __('Stock added successfully. :name now has :qty :unit', [
                    'name' => $product->name,
                    'qty' => number_format($product->fresh()->quantity, 2),
                    'unit' => $product->unit ?? 'pcs',
                ]));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Failed to add stock: ') . $e->getMessage());
        }
    }

    /**
     * Show deduct stock form
     */
    public function deductForm()
    {
        $rawMaterials = RawMaterial::where('quantity', '>', 0)->orderBy('name')->get();
        $products = Product::where('quantity', '>', 0)->orderBy('name')->get();
        return view('stock-management.deduct', compact('rawMaterials', 'products'));
    }

    /**
     * Deduct stock from raw material
     */
    public function deductRawMaterialStock(Request $request)
    {
        $validated = $request->validate([
            'raw_material_id' => 'required|exists:raw_materials,id',
            'quantity' => 'required|numeric|min:0.001',
            'type' => 'required|in:usage,waste,adjustment,damage',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $material = RawMaterial::findOrFail($validated['raw_material_id']);
            
            if ($material->quantity < $validated['quantity']) {
                return back()
                    ->withInput()
                    ->with('error', __('Insufficient stock. Available: :qty :unit', [
                        'qty' => $material->quantity,
                        'unit' => $material->unit,
                    ]));
            }

            $this->rawMaterialService->adjustStock(
                $material,
                -$validated['quantity'],
                $validated['notes'] ?? "Stock deduction: {$validated['type']}",
                $validated['type']
            );

            return redirect()
                ->route('stock-management.index')
                ->with('success', __('Stock deducted successfully.'));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Failed to deduct stock: ') . $e->getMessage());
        }
    }

    /**
     * Deduct stock from product
     */
    public function deductProductStock(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'type' => 'required|in:sale,waste,adjustment,damage',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $product = Product::findOrFail($validated['product_id']);
            
            $this->productionService->deductProductStock(
                $product,
                $validated['quantity'],
                $validated['type'],
                $validated['notes']
            );

            return redirect()
                ->route('stock-management.index')
                ->with('success', __('Stock deducted successfully.'));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Stock movement history
     */
    public function movements(Request $request)
    {
        $query = StockMovement::with(['product', 'rawMaterial', 'creator']);

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by item type (product or raw material)
        if ($request->filled('item_type')) {
            if ($request->item_type === 'product') {
                $query->whereNotNull('product_id');
            } else {
                $query->whereNotNull('raw_material_id');
            }
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(25);

        $movementTypes = [
            'purchase' => __('Purchase'),
            'production' => __('Production'),
            'usage' => __('Usage'),
            'sale' => __('Sale'),
            'adjustment' => __('Adjustment'),
            'waste' => __('Waste'),
            'return' => __('Return'),
            'damage' => __('Damage'),
        ];

        return view('stock-management.movements', compact('movements', 'movementTypes'));
    }

    /**
     * Bulk stock adjustment
     */
    public function bulkAdjustmentForm()
    {
        $rawMaterials = RawMaterial::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        return view('stock-management.bulk-adjustment', compact('rawMaterials', 'products'));
    }

    /**
     * Process bulk stock adjustment
     */
    public function bulkAdjustment(Request $request)
    {
        $validated = $request->validate([
            'adjustments' => 'required|array|min:1',
            'adjustments.*.type' => 'required|in:raw_material,product',
            'adjustments.*.id' => 'required|integer',
            'adjustments.*.quantity' => 'required|numeric',
            'adjustments.*.reason' => 'required|string|max:255',
        ]);

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        DB::beginTransaction();
        try {
            foreach ($validated['adjustments'] as $adjustment) {
                try {
                    if ($adjustment['type'] === 'raw_material') {
                        $material = RawMaterial::findOrFail($adjustment['id']);
                        $this->rawMaterialService->adjustStock(
                            $material,
                            $adjustment['quantity'],
                            $adjustment['reason'],
                            'adjustment'
                        );
                    } else {
                        $product = Product::findOrFail($adjustment['id']);
                        if ($adjustment['quantity'] > 0) {
                            $this->productionService->addProductStock(
                                $product,
                                $adjustment['quantity'],
                                null,
                                'adjustment',
                                $adjustment['reason']
                            );
                        } else {
                            $this->productionService->deductProductStock(
                                $product,
                                abs($adjustment['quantity']),
                                'adjustment',
                                $adjustment['reason']
                            );
                        }
                    }
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = $e->getMessage();
                }
            }

            DB::commit();

            return redirect()
                ->route('stock-management.index')
                ->with('success', __(':count adjustments processed successfully.', ['count' => $results['success']]));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', __('Bulk adjustment failed: ') . $e->getMessage());
        }
    }

    /**
     * View all stock - comprehensive inventory list
     */
    public function viewAllStock(Request $request)
    {
        // Get raw materials with filters
        $rawMaterialsQuery = RawMaterial::with('supplier');
        
        // Get products with filters
        $productsQuery = Product::with('category');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $rawMaterialsQuery->where('name', 'like', "%{$search}%");
            $productsQuery->where('name', 'like', "%{$search}%");
        }

        // Apply stock status filter
        if ($request->filled('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $rawMaterialsQuery->where('quantity', '>', 0)
                        ->whereColumn('quantity', '>', 'minimum_stock_level');
                    $productsQuery->where('quantity', '>', 0);
                    break;
                case 'low_stock':
                    $rawMaterialsQuery->where('quantity', '>', 0)
                        ->whereColumn('quantity', '<=', 'minimum_stock_level');
                    $productsQuery->where('quantity', '>', 0)
                        ->whereColumn('quantity', '<=', 'minimum_stock_level');
                    break;
                case 'out_of_stock':
                    $rawMaterialsQuery->where('quantity', '<=', 0);
                    $productsQuery->where('quantity', '<=', 0);
                    break;
            }
        }

        // Get type filter
        $type = $request->input('type', 'all');

        $rawMaterials = collect();
        $products = collect();

        if ($type === 'all' || $type === 'raw_materials') {
            $rawMaterials = $rawMaterialsQuery->orderBy('name')->get();
        }

        if ($type === 'all' || $type === 'products') {
            $products = $productsQuery->orderBy('name')->get();
        }

        // Calculate totals
        $totals = [
            'raw_materials_count' => $rawMaterials->count(),
            'raw_materials_value' => $rawMaterials->sum(fn($m) => $m->quantity * $m->cost_per_unit),
            'products_count' => $products->count(),
            'products_value' => $products->sum(fn($p) => $p->quantity * ($p->cost ?? 0)),
            'low_stock_count' => $rawMaterials->filter(fn($m) => $m->isLowStock())->count() + 
                                 $products->filter(fn($p) => isset($p->minimum_stock_level) && $p->quantity <= $p->minimum_stock_level && $p->quantity > 0)->count(),
            'out_of_stock_count' => $rawMaterials->filter(fn($m) => $m->quantity <= 0)->count() + 
                                    $products->filter(fn($p) => $p->quantity <= 0)->count(),
        ];

        // Get categories and suppliers for filters
        $categories = \App\Models\Category::orderBy('name')->get();
        $suppliers = \App\Models\Supplier::orderBy('name')->get();

        return view('stock-management.view-all', compact(
            'rawMaterials',
            'products',
            'totals',
            'categories',
            'suppliers',
            'type'
        ));
    }

    /**
     * Export all stock to CSV
     */
    public function exportStock(Request $request)
    {
        $type = $request->input('type', 'all');
        
        $filename = 'stock_inventory_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($type) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Type',
                'Name',
                'Category/Supplier',
                'Quantity',
                'Unit',
                'Cost Per Unit',
                'Total Value',
                'Minimum Stock',
                'Status',
            ]);

            // Raw materials
            if ($type === 'all' || $type === 'raw_materials') {
                $rawMaterials = RawMaterial::with('supplier')->orderBy('name')->get();
                foreach ($rawMaterials as $material) {
                    $status = $material->quantity <= 0 ? 'Out of Stock' : 
                             ($material->isLowStock() ? 'Low Stock' : 'In Stock');
                    fputcsv($file, [
                        'Raw Material',
                        $material->name,
                        $material->supplier?->name ?? 'N/A',
                        $material->quantity,
                        $material->unit,
                        $material->cost_per_unit,
                        $material->quantity * $material->cost_per_unit,
                        $material->minimum_stock_level,
                        $status,
                    ]);
                }
            }

            // Products
            if ($type === 'all' || $type === 'products') {
                $products = Product::with('category')->orderBy('name')->get();
                foreach ($products as $product) {
                    $status = $product->quantity <= 0 ? 'Out of Stock' : 
                             (isset($product->minimum_stock_level) && $product->quantity <= $product->minimum_stock_level ? 'Low Stock' : 'In Stock');
                    fputcsv($file, [
                        'Product',
                        $product->name,
                        $product->category?->name ?? 'N/A',
                        $product->quantity,
                        $product->unit ?? 'pcs',
                        $product->cost ?? 0,
                        $product->quantity * ($product->cost ?? 0),
                        $product->minimum_stock_level ?? 0,
                        $status,
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Stock valuation report
     */
    public function valuation()
    {
        $rawMaterials = RawMaterial::with('supplier')
            ->orderBy('name')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'type' => 'raw_material',
                    'quantity' => $m->quantity,
                    'unit' => $m->unit,
                    'cost_per_unit' => $m->cost_per_unit,
                    'total_value' => $m->quantity * $m->cost_per_unit,
                    'supplier' => $m->supplier?->name,
                    'status' => $m->getStockStatus(),
                ];
            });

        $products = Product::with('category')
            ->orderBy('name')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'type' => 'product',
                    'quantity' => $p->quantity,
                    'unit' => $p->unit ?? 'pcs',
                    'cost_per_unit' => $p->cost ?? 0,
                    'total_value' => $p->quantity * ($p->cost ?? 0),
                    'category' => $p->category?->name,
                    'status' => $p->quantity <= 0 ? 'out_of_stock' : 
                               (isset($p->minimum_stock_level) && $p->quantity <= $p->minimum_stock_level ? 'low' : 'normal'),
                ];
            });

        $totalRawMaterialValue = $rawMaterials->sum('total_value');
        $totalProductValue = $products->sum('total_value');

        return view('stock-management.valuation', compact(
            'rawMaterials',
            'products',
            'totalRawMaterialValue',
            'totalProductValue'
        ));
    }
}
