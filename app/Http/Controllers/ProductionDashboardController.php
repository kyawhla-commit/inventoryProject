<?php

namespace App\Http\Controllers;

use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RawMaterialUsage;
use App\Models\RawMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProductionDashboardController extends Controller
{
    /**
     * Display the production dashboard with relationships to orders and stock
     */
    public function index(Request $request)
    {
        $productionStats = [
            'total_plans' => ProductionPlan::count(),
            'in_progress' => ProductionPlan::where('status', 'in_progress')->count(),
            'completed' => ProductionPlan::where('status', 'completed')->count(),
            'pending' => ProductionPlan::whereIn('status', ['draft', 'approved'])->count(),
        ];

        // Get REAL stock levels - simple and accurate
        $stockLevels = Product::with(['category'])
            ->select('id', 'name', 'quantity', 'unit', 'minimum_stock_level', 'price', 'cost')
            ->get()
            ->map(function ($product) {
                // Calculate stock status based on actual quantity
                if ($product->quantity <= 0) {
                    $product->stock_status = 'out_of_stock';
                    $product->stock_status_color = 'danger';
                } elseif ($product->quantity <= $product->minimum_stock_level) {
                    $product->stock_status = 'low';
                    $product->stock_status_color = 'warning';
                } else {
                    $product->stock_status = 'normal';
                    $product->stock_status_color = 'success';
                }

                // Calculate stock value
                $product->stock_value = $product->quantity * $product->cost;

                return $product;
            });

        // Get critical raw materials - simple query
        $criticalMaterials = RawMaterial::select('id', 'name', 'quantity', 'unit', 'minimum_stock_level', 'cost_per_unit')
            ->where('quantity', '<=', DB::raw('minimum_stock_level'))
            ->get()
            ->map(function ($material) {
                // Calculate days remaining based on average usage
                $avgDailyUsage = RawMaterialUsage::where('raw_material_id', $material->id)
                    ->where('usage_date', '>=', now()->subDays(30))
                    ->avg('quantity_used');

                $material->monthly_usage = $avgDailyUsage * 30;
                $material->days_remaining = $avgDailyUsage > 0
                    ? round(($material->quantity / $avgDailyUsage), 1)
                    : null;

                $material->stock_value = $material->quantity * $material->cost_per_unit;

                return $material;
            });

        // Filter by date range
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Completed Production Plans
        $completedPlans = ProductionPlan::with(['productionPlanItems.product', 'productionPlanItems.order'])
            ->where('status', 'completed')
            ->whereBetween('actual_end_date', [$startDate, $endDate])
            ->orderBy('actual_end_date', 'desc')
            ->get();

        // Production Summary Statistics
        $totalProduced = ProductionPlanItem::whereHas('productionPlan', function ($query) use ($startDate, $endDate) {
            $query->where('status', 'completed')
                ->whereBetween('actual_end_date', [$startDate, $endDate]);
        })
            ->sum('actual_quantity');

        $totalProductionCost = $completedPlans->sum('total_actual_cost');
        $totalEstimatedCost = $completedPlans->sum('total_estimated_cost');
        $costVariance = $totalProductionCost - $totalEstimatedCost;
        $costVariancePercentage = $totalEstimatedCost > 0 ? ($costVariance / $totalEstimatedCost) * 100 : 0;

        // Products Produced with Stock Levels
        $productsProduced = ProductionPlanItem::with(['product', 'productionPlan'])
            ->whereHas('productionPlan', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                    ->whereBetween('actual_end_date', [$startDate, $endDate]);
            })
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) use ($startDate, $endDate) {
                $product = $items->first()->product;
                if (!$product) return null;

                $totalProduced = $items->sum('actual_quantity');

                // Get raw material usage for this product within the date range
                $usages = \App\Models\RawMaterialUsage::where('product_id', $product->id)
                    ->where('usage_type', 'production')
                    ->whereBetween('usage_date', [$startDate, $endDate])
                    ->get();

                $totalCost = $usages->sum('total_cost');

                // If no specific usage data, fallback to calculations
                if ($totalCost == 0) {
                    // Try to calculate from quantity_used and cost_per_unit
                    $totalCost = $usages->sum(function ($usage) {
                        return $usage->quantity_used * $usage->cost_per_unit;
                    });

                    // Final fallback: use product cost
                    if ($totalCost == 0) {
                        $totalCost = $totalProduced * $product->cost;
                    }
                }

                $productionCount = $items->count();

                return [
                    'product' => $product,
                    'total_produced' => $totalProduced,
                    'current_stock' => $product->quantity,
                    'total_cost' => $totalCost,
                    'production_count' => $productionCount,
                    'avg_cost_per_unit' => $totalProduced > 0 ? $totalCost / $totalProduced : 0,
                    'stock_status' => $this->getStockStatus($product),
                    'stock_value' => $product->quantity * $product->cost,
                    'raw_material_usages_count' => $usages->count(), // for debugging
                ];
            })->filter()->sortByDesc('total_produced');


        // Orders Fulfilled through Production
        $ordersFulfilled = ProductionPlanItem::with(['order.customer', 'order.items', 'product', 'productionPlan'])
            ->whereNotNull('order_id')
            ->whereHas('productionPlan', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                    ->whereBetween('actual_end_date', [$startDate, $endDate]);
            })
            ->get()
            ->groupBy('order_id')
            ->map(function ($items) {
                $order = $items->first()->order;

                if (!$order) {
                    return null;
                }

                $producedItems = $items->map(function ($item) {
                    return [
                        'product' => $item->product,
                        'quantity_produced' => $item->actual_quantity,
                        'production_plan' => $item->productionPlan,
                    ];
                });

                // Calculate order fulfillment percentage
                $orderItems = $order->items;
                $fulfillmentPercentage = 0;

                if ($orderItems && $orderItems->count() > 0) {
                    $fulfilledCount = 0;
                    foreach ($orderItems as $orderItem) {
                        $produced = $items->where('product_id', $orderItem->product_id)
                            ->sum('actual_quantity');
                        if ($produced >= $orderItem->quantity) {
                            $fulfilledCount++;
                        }
                    }
                    $fulfillmentPercentage = ($fulfilledCount / $orderItems->count()) * 100;
                }

                return [
                    'order' => $order,
                    'customer' => $order->customer,
                    'produced_items' => $producedItems,
                    'fulfillment_percentage' => $fulfillmentPercentage,
                    'total_items' => $orderItems ? $orderItems->count() : 0,
                    'fulfilled_items' => $producedItems->count(),
                ];
            })->filter();

        // Stock Movement Analysis
        $stockMovements = Product::whereHas('productionPlanItems', function ($query) use ($startDate, $endDate) {
            $query->whereHas('productionPlan', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'completed')
                    ->whereBetween('actual_end_date', [$startDate, $endDate]);
            });
        })
            ->with(['productionPlanItems' => function ($query) use ($startDate, $endDate) {
                $query->whereHas('productionPlan', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'completed')
                        ->whereBetween('actual_end_date', [$startDate, $endDate]);
                });
            }])
            ->get()
            ->map(function ($product) {
                $produced = $product->productionPlanItems->sum('actual_quantity');
                return [
                    'product' => $product,
                    'produced_quantity' => $produced,
                    'current_stock' => $product->quantity,
                    'minimum_stock' => $product->minimum_stock_level,
                    'stock_coverage_days' => $this->calculateStockCoverageDays($product),
                    'stock_status' => $this->getStockStatus($product),
                    'stock_value' => $product->quantity * $product->cost,
                ];
            });

        // Production Efficiency Metrics
        $efficiencyMetrics = [
            'total_plans_completed' => $completedPlans->count(),
            'avg_completion_time' => $this->calculateAvgCompletionTime($completedPlans),
            'on_time_completion_rate' => $this->calculateOnTimeCompletionRate($completedPlans),
            'quality_metrics' => [
                'avg_efficiency' => $this->calculateAvgEfficiency($completedPlans),
                'variance_rate' => $costVariancePercentage,
            ],
        ];

        // Top Performing Products
        $topProducts = $productsProduced->take(10);

        // Low Stock Alert
        $lowStockProducts = $stockLevels->filter(function ($product) {
            return in_array($product->stock_status, ['low', 'out_of_stock']);
        });

        return view('production-plans.dashboard', compact(
            'productionStats',
            'stockLevels',
            'criticalMaterials',
            'completedPlans',
            'totalProduced',
            'totalProductionCost',
            'totalEstimatedCost',
            'costVariance',
            'costVariancePercentage',
            'productsProduced',
            'ordersFulfilled',
            'stockMovements',
            'efficiencyMetrics',
            'topProducts',
            'lowStockProducts',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Calculate stock coverage in days based on average production/sales
     */
    private function calculateStockCoverageDays($product)
    {
        // Get average daily production from the last 30 days
        $avgDailyProduction = ProductionPlanItem::where('product_id', $product->id)
            ->whereHas('productionPlan', function ($query) {
                $query->where('status', 'completed')
                    ->where('actual_end_date', '>=', now()->subDays(30));
            })
            ->avg('actual_quantity');

        if ($avgDailyProduction > 0) {
            return round($product->quantity / ($avgDailyProduction / 30), 1);
        }

        return null;
    }

    /**
     * Get stock status based on current quantity vs minimum
     */
    private function getStockStatus($product)
    {
        if ($product->quantity <= 0) {
            return 'out_of_stock';
        } elseif ($product->quantity <= $product->minimum_stock_level * 0.5) {
            return 'critical';
        } elseif ($product->quantity <= $product->minimum_stock_level) {
            return 'low';
        } else {
            return 'normal';
        }
    }

    /**
     * Calculate average completion time for production plans
     */
    private function calculateAvgCompletionTime($plans)
    {
        if ($plans->count() === 0) return 0;

        $totalDays = 0;
        $count = 0;

        foreach ($plans as $plan) {
            if ($plan->actual_start_date && $plan->actual_end_date) {
                $totalDays += $plan->actual_start_date->diffInDays($plan->actual_end_date);
                $count++;
            }
        }

        return $count > 0 ? round($totalDays / $count, 1) : 0;
    }

    /**
     * Calculate on-time completion rate
     */
    private function calculateOnTimeCompletionRate($plans)
    {
        if ($plans->count() === 0) return 0;

        $onTimeCount = $plans->filter(function ($plan) {
            return $plan->actual_end_date &&
                $plan->planned_end_date &&
                $plan->actual_end_date->lte($plan->planned_end_date);
        })->count();

        return round(($onTimeCount / $plans->count()) * 100, 1);
    }

    /**
     * Calculate average production efficiency
     */
    private function calculateAvgEfficiency($plans)
    {
        $items = ProductionPlanItem::whereIn('production_plan_id', $plans->pluck('id'))
            ->whereNotNull('actual_quantity')
            ->where('planned_quantity', '>', 0)
            ->get();

        if ($items->count() === 0) return 0;

        $totalEfficiency = $items->sum(function ($item) {
            return ($item->actual_quantity / $item->planned_quantity) * 100;
        });

        return round($totalEfficiency / $items->count(), 1);
    }
}
