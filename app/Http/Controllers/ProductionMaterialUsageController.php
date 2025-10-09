<?php

namespace App\Http\Controllers;

use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialUsage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionMaterialUsageController extends Controller
{
    /**
     * Display material usage dashboard for production
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Get production-related material usage
        $usages = RawMaterialUsage::with(['rawMaterial', 'product', 'order', 'recordedBy'])
            ->where('usage_type', 'production')
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->orderBy('usage_date', 'desc')
            ->paginate(20);

        // Material usage summary
        $summaryStats = RawMaterialUsage::where('usage_type', 'production')
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total_records, SUM(quantity_used) as total_quantity, SUM(total_cost) as total_cost')
            ->first();

        // Top used materials
        $topMaterials = RawMaterialUsage::with('rawMaterial')
            ->where('usage_type', 'production')
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->selectRaw('raw_material_id, SUM(quantity_used) as total_used, SUM(total_cost) as total_cost, COUNT(*) as usage_count')
            ->groupBy('raw_material_id')
            ->orderBy('total_cost', 'desc')
            ->limit(10)
            ->get();

        // Materials running low after production usage
        $lowStockMaterials = RawMaterial::where('quantity', '<=', DB::raw('minimum_stock_level'))
            ->whereHas('usages', function($query) use ($startDate, $endDate) {
                $query->where('usage_type', 'production')
                      ->whereBetween('usage_date', [$startDate, $endDate]);
            })
            ->with(['usages' => function($query) use ($startDate, $endDate) {
                $query->where('usage_type', 'production')
                      ->whereBetween('usage_date', [$startDate, $endDate]);
            }])
            ->get();

        // Waste analysis
        $wasteStats = RawMaterialUsage::where('usage_type', 'waste')
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as waste_records, SUM(quantity_used) as total_waste, SUM(total_cost) as waste_cost')
            ->first();

        return view('production-material-usage.index', compact(
            'usages',
            'summaryStats',
            'topMaterials',
            'lowStockMaterials',
            'wasteStats',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Record material usage for a production plan
     */
    public function recordForProduction(ProductionPlan $productionPlan)
    {
        $productionPlan->load(['productionPlanItems.product.rawMaterials', 'productionPlanItems.recipe.recipeItems.rawMaterial']);

        // Calculate expected material requirements
        $materialRequirements = $productionPlan->calculateMaterialRequirements();

        // Get available raw materials
        $rawMaterials = RawMaterial::where('quantity', '>', 0)->get();

        return view('production-material-usage.record', compact('productionPlan', 'materialRequirements', 'rawMaterials'));
    }

    /**
     * Store material usage for production plan
     */
    public function storeForProduction(Request $request, ProductionPlan $productionPlan)
    {
        $request->validate([
            'plan_item_id' => 'required|exists:production_plan_items,id',
            'actual_quantity' => 'required|numeric|min:0',
            'material_usages' => 'required|array|min:1',
            'material_usages.*.raw_material_id' => 'required|exists:raw_materials,id',
            'material_usages.*.quantity_used' => 'required|numeric|min:0.001',
            'material_usages.*.waste_quantity' => 'nullable|numeric|min:0',
            'material_usages.*.notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $productionPlan) {
            $planItem = ProductionPlanItem::findOrFail($request->plan_item_id);
            
            // Update actual quantity produced
            $planItem->update([
                'actual_quantity' => $request->actual_quantity,
                'status' => 'completed',
                'actual_end_date' => now(),
            ]);

            $totalActualCost = 0;

            // Record material usages
            foreach ($request->material_usages as $usage) {
                if ($usage['quantity_used'] > 0) {
                    $rawMaterial = RawMaterial::findOrFail($usage['raw_material_id']);
                    
                    // Check stock availability
                    if ($rawMaterial->quantity < $usage['quantity_used']) {
                        throw new \Exception("Insufficient stock for {$rawMaterial->name}. Available: {$rawMaterial->quantity} {$rawMaterial->unit}");
                    }

                    // Record production usage
                    $materialUsage = RawMaterialUsage::create([
                        'raw_material_id' => $usage['raw_material_id'],
                        'product_id' => $planItem->product_id,
                        'order_id' => $planItem->order_id,
                        'quantity_used' => $usage['quantity_used'],
                        'cost_per_unit' => $rawMaterial->cost_per_unit,
                        'total_cost' => $usage['quantity_used'] * $rawMaterial->cost_per_unit,
                        'usage_date' => now(),
                        'usage_type' => 'production',
                        'notes' => $usage['notes'] ?? "Production Plan: {$productionPlan->plan_number}, Item: {$planItem->product->name}",
                        'batch_number' => $productionPlan->plan_number,
                        'recorded_by' => Auth::id(),
                    ]);

                    $materialUsage->updateRawMaterialStock();
                    $totalActualCost += $materialUsage->total_cost;

                    // Record waste if any
                    if (!empty($usage['waste_quantity']) && $usage['waste_quantity'] > 0) {
                        $wasteUsage = RawMaterialUsage::create([
                            'raw_material_id' => $usage['raw_material_id'],
                            'product_id' => $planItem->product_id,
                            'order_id' => $planItem->order_id,
                            'quantity_used' => $usage['waste_quantity'],
                            'cost_per_unit' => $rawMaterial->cost_per_unit,
                            'total_cost' => $usage['waste_quantity'] * $rawMaterial->cost_per_unit,
                            'usage_date' => now(),
                            'usage_type' => 'waste',
                            'notes' => "Waste from Production Plan: {$productionPlan->plan_number}",
                            'batch_number' => $productionPlan->plan_number,
                            'recorded_by' => Auth::id(),
                        ]);

                        $wasteUsage->updateRawMaterialStock();
                        $totalActualCost += $wasteUsage->total_cost;
                    }
                }
            }

            // Update actual material cost
            $planItem->update(['actual_material_cost' => $totalActualCost]);

            // Update production plan total actual cost
            $productionPlan->refresh();
            $productionPlan->update([
                'total_actual_cost' => $productionPlan->productionPlanItems->sum('actual_material_cost')
            ]);

            // Update product stock
            if ($request->actual_quantity > 0) {
                $planItem->product->increment('quantity', $request->actual_quantity);
            }
        });

        return redirect()->route('production-plans.show', $productionPlan)
            ->with('success', 'Material usage recorded successfully and stock updated.');
    }

    /**
     * Material efficiency analysis
     */
    public function efficiency(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Get completed production plans with material usage
        $productionPlans = ProductionPlan::with(['productionPlanItems.product'])
            ->where('status', 'completed')
            ->whereBetween('actual_end_date', [$startDate, $endDate])
            ->get();

        // Calculate efficiency metrics
        $efficiencyData = [];

        foreach ($productionPlans as $plan) {
            foreach ($plan->productionPlanItems as $item) {
                if (!$item->recipe_id) continue;

                $expectedMaterials = $item->recipe->calculateMaterialRequirements($item->planned_quantity);
                $actualMaterials = RawMaterialUsage::where('usage_type', 'production')
                    ->where('product_id', $item->product_id)
                    ->whereBetween('usage_date', [
                        $plan->actual_start_date ?? $plan->planned_start_date,
                        $plan->actual_end_date ?? $plan->planned_end_date
                    ])
                    ->get()
                    ->groupBy('raw_material_id');

                $itemEfficiency = [
                    'plan' => $plan,
                    'item' => $item,
                    'expected_cost' => $item->estimated_material_cost,
                    'actual_cost' => $item->actual_material_cost,
                    'variance' => $item->actual_material_cost - $item->estimated_material_cost,
                    'variance_percentage' => $item->estimated_material_cost > 0 
                        ? (($item->actual_material_cost - $item->estimated_material_cost) / $item->estimated_material_cost) * 100 
                        : 0,
                    'materials' => [],
                ];

                foreach ($expectedMaterials as $expected) {
                    $materialId = $expected['raw_material_id'];
                    $actual = $actualMaterials->get($materialId);
                    
                    $actualQty = $actual ? $actual->sum('quantity_used') : 0;
                    $expectedQty = $expected['total_required'];
                    
                    $itemEfficiency['materials'][] = [
                        'material' => $expected['raw_material'],
                        'expected_quantity' => $expectedQty,
                        'actual_quantity' => $actualQty,
                        'difference' => $actualQty - $expectedQty,
                        'efficiency' => $expectedQty > 0 ? ($actualQty / $expectedQty) * 100 : 0,
                    ];
                }

                $efficiencyData[] = $itemEfficiency;
            }
        }

        return view('production-material-usage.efficiency', compact('efficiencyData', 'startDate', 'endDate'));
    }

    /**
     * Material stock impact from production
     */
    public function stockImpact(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Get all raw materials with production usage
        $materials = RawMaterial::with(['supplier'])
            ->whereHas('usages', function($query) use ($startDate, $endDate) {
                $query->where('usage_type', 'production')
                      ->whereBetween('usage_date', [$startDate, $endDate]);
            })
            ->get();

        $stockImpactData = $materials->map(function($material) use ($startDate, $endDate) {
            $usedInPeriod = $material->usages()
                ->where('usage_type', 'production')
                ->whereBetween('usage_date', [$startDate, $endDate])
                ->sum('quantity_used');

            $wasteInPeriod = $material->usages()
                ->where('usage_type', 'waste')
                ->whereBetween('usage_date', [$startDate, $endDate])
                ->sum('quantity_used');

            $avgDailyUsage = $material->usages()
                ->where('usage_type', 'production')
                ->whereBetween('usage_date', [now()->subDays(30), now()])
                ->avg('quantity_used') ?? 0;

            $daysUntilStockout = $avgDailyUsage > 0 
                ? $material->quantity / $avgDailyUsage 
                : null;

            return [
                'material' => $material,
                'current_stock' => $material->quantity,
                'minimum_stock' => $material->minimum_stock_level,
                'used_in_period' => $usedInPeriod,
                'waste_in_period' => $wasteInPeriod,
                'total_consumed' => $usedInPeriod + $wasteInPeriod,
                'avg_daily_usage' => $avgDailyUsage,
                'days_until_stockout' => $daysUntilStockout,
                'stock_status' => $this->getStockStatus($material),
                'reorder_needed' => $material->quantity <= $material->minimum_stock_level,
            ];
        });

        return view('production-material-usage.stock-impact', compact('stockImpactData', 'startDate', 'endDate'));
    }

    /**
     * Waste analysis report
     */
    public function wasteAnalysis(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Waste by material
        $wasteByMaterial = RawMaterialUsage::with('rawMaterial')
            ->where('usage_type', 'waste')
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->selectRaw('raw_material_id, SUM(quantity_used) as total_waste, SUM(total_cost) as waste_cost, COUNT(*) as waste_count')
            ->groupBy('raw_material_id')
            ->orderBy('waste_cost', 'desc')
            ->get();

        // Waste by product
        $wasteByProduct = RawMaterialUsage::with(['product', 'rawMaterial'])
            ->where('usage_type', 'waste')
            ->whereNotNull('product_id')
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->selectRaw('product_id, raw_material_id, SUM(quantity_used) as total_waste, SUM(total_cost) as waste_cost')
            ->groupBy('product_id', 'raw_material_id')
            ->get()
            ->groupBy('product_id');

        // Daily waste trend
        $wasteByDate = RawMaterialUsage::where('usage_type', 'waste')
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->selectRaw('DATE(usage_date) as date, SUM(quantity_used) as total_waste, SUM(total_cost) as waste_cost')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Total waste statistics
        $totalWasteStats = RawMaterialUsage::where('usage_type', 'waste')
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as records, SUM(quantity_used) as total_quantity, SUM(total_cost) as total_cost')
            ->first();

        // Production usage for comparison
        $productionUsageStats = RawMaterialUsage::where('usage_type', 'production')
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->selectRaw('SUM(total_cost) as total_cost')
            ->first();

        $wastePercentage = $productionUsageStats->total_cost > 0 
            ? ($totalWasteStats->total_cost / $productionUsageStats->total_cost) * 100 
            : 0;

        return view('production-material-usage.waste-analysis', compact(
            'wasteByMaterial',
            'wasteByProduct',
            'wasteByDate',
            'totalWasteStats',
            'productionUsageStats',
            'wastePercentage',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Get stock status classification
     */
    private function getStockStatus($material)
    {
        if ($material->quantity <= 0) {
            return 'out_of_stock';
        } elseif ($material->quantity <= $material->minimum_stock_level * 0.5) {
            return 'critical';
        } elseif ($material->quantity <= $material->minimum_stock_level) {
            return 'low';
        } else {
            return 'normal';
        }
    }

    /**
     * Material requirements vs actual usage comparison
     */
    public function requirementsComparison(ProductionPlan $productionPlan)
    {
        $productionPlan->load(['productionPlanItems.product', 'productionPlanItems.recipe.recipeItems.rawMaterial']);

        // Expected requirements
        $expectedRequirements = $productionPlan->calculateMaterialRequirements();

        // Actual usage
        $actualUsage = RawMaterialUsage::where('usage_type', 'production')
            ->where('batch_number', $productionPlan->plan_number)
            ->with('rawMaterial')
            ->get()
            ->groupBy('raw_material_id');

        // Waste
        $waste = RawMaterialUsage::where('usage_type', 'waste')
            ->where('batch_number', $productionPlan->plan_number)
            ->with('rawMaterial')
            ->get()
            ->groupBy('raw_material_id');

        $comparison = [];

        foreach ($expectedRequirements as $expected) {
            $materialId = $expected['raw_material_id'];
            $actualUsed = $actualUsage->get($materialId);
            $wasteQty = $waste->get($materialId);

            $comparison[] = [
                'material' => $expected['raw_material'],
                'expected_quantity' => $expected['total_required'],
                'expected_cost' => $expected['estimated_cost'],
                'actual_quantity' => $actualUsed ? $actualUsed->sum('quantity_used') : 0,
                'actual_cost' => $actualUsed ? $actualUsed->sum('total_cost') : 0,
                'waste_quantity' => $wasteQty ? $wasteQty->sum('quantity_used') : 0,
                'waste_cost' => $wasteQty ? $wasteQty->sum('total_cost') : 0,
                'variance_quantity' => ($actualUsed ? $actualUsed->sum('quantity_used') : 0) - $expected['total_required'],
                'variance_cost' => ($actualUsed ? $actualUsed->sum('total_cost') : 0) - $expected['estimated_cost'],
                'unit' => $expected['unit'],
            ];
        }

        return view('production-material-usage.requirements-comparison', compact('productionPlan', 'comparison'));
    }
}
