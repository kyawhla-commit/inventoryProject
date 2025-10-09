<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\InventoryTransaction;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function salesReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|in:daily,weekly,monthly,yearly,custom',
            'date_from' => 'required_if:period,custom|date',
            'date_to' => 'required_if:period,custom|date|after_or_equal:date_from',
            'group_by' => 'nullable|in:day,week,month,year,product,customer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Determine date range
        $dateRange = $this->getDateRange($request->period, $request->date_from, $request->date_to);
        
        $query = Sale::with(['customer', 'items.product'])
            ->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']]);

        $sales = $query->get();

        // Calculate totals
        $totalSales = $sales->sum('total_amount');
        $totalOrders = $sales->count();
        $averageOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;

        // Group data based on request
        $groupedData = $this->groupSalesData($sales, $request->group_by ?? 'day');

        // Top products
        $topProducts = $sales->flatMap->items
            ->groupBy('product_id')
            ->map(function ($items) {
                $product = $items->first()->product;
                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity_sold' => $items->sum('quantity'),
                    'total_revenue' => $items->sum(function ($item) {
                        return $item->quantity * $item->unit_price;
                    }),
                ];
            })
            ->sortByDesc('total_revenue')
            ->take(10)
            ->values();

        // Top customers
        $topCustomers = $sales->groupBy('customer_id')
            ->map(function ($customerSales) {
                $customer = $customerSales->first()->customer;
                return [
                    'customer_id' => $customer->id ?? null,
                    'customer_name' => $customer->name ?? 'Walk-in Customer',
                    'total_orders' => $customerSales->count(),
                    'total_spent' => $customerSales->sum('total_amount'),
                ];
            })
            ->sortByDesc('total_spent')
            ->take(10)
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $request->period,
                'date_range' => $dateRange,
                'summary' => [
                    'total_sales' => $totalSales,
                    'total_orders' => $totalOrders,
                    'average_order_value' => round($averageOrderValue, 2),
                ],
                'grouped_data' => $groupedData,
                'top_products' => $topProducts,
                'top_customers' => $topCustomers,
            ]
        ]);
    }

    public function inventoryValuationReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'valuation_method' => 'nullable|in:cost,selling_price,average',
            'include_raw_materials' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $valuationMethod = $request->get('valuation_method', 'cost');
        $includeRawMaterials = $request->boolean('include_raw_materials', true);

        // Product valuation
        $products = Product::with('category')->get()->map(function ($product) use ($valuationMethod) {
            $unitValue = match($valuationMethod) {
                'selling_price' => $product->price,
                'average' => ($product->cost + $product->price) / 2,
                default => $product->cost,
            };

            return [
                'type' => 'product',
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category->name ?? 'Uncategorized',
                'quantity' => $product->quantity,
                'unit_value' => $unitValue,
                'total_value' => $product->quantity * $unitValue,
                'unit' => $product->unit ?? 'pcs',
            ];
        });

        $productTotalValue = $products->sum('total_value');

        $data = [
            'valuation_method' => $valuationMethod,
            'valuation_date' => now()->format('Y-m-d H:i:s'),
            'products' => [
                'items' => $products,
                'total_value' => $productTotalValue,
                'total_items' => $products->count(),
            ],
        ];

        // Raw materials valuation (if requested)
        if ($includeRawMaterials) {
            $rawMaterials = RawMaterial::get()->map(function ($rawMaterial) {
                return [
                    'type' => 'raw_material',
                    'id' => $rawMaterial->id,
                    'name' => $rawMaterial->name,
                    'category' => 'Raw Materials',
                    'quantity' => $rawMaterial->current_stock,
                    'unit_value' => $rawMaterial->cost_per_unit,
                    'total_value' => $rawMaterial->current_stock * $rawMaterial->cost_per_unit,
                    'unit' => $rawMaterial->unit,
                ];
            });

            $rawMaterialTotalValue = $rawMaterials->sum('total_value');

            $data['raw_materials'] = [
                'items' => $rawMaterials,
                'total_value' => $rawMaterialTotalValue,
                'total_items' => $rawMaterials->count(),
            ];

            $data['grand_total'] = $productTotalValue + $rawMaterialTotalValue;
        } else {
            $data['grand_total'] = $productTotalValue;
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function profitLossReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|in:monthly,quarterly,yearly,custom',
            'date_from' => 'required_if:period,custom|date',
            'date_to' => 'required_if:period,custom|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $dateRange = $this->getDateRange($request->period, $request->date_from, $request->date_to);

        // Revenue from sales
        $sales = Sale::with('items.product')
            ->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']])
            ->get();

        $totalRevenue = $sales->sum('total_amount');

        // Cost of goods sold (COGS)
        $cogs = $sales->flatMap->items->sum(function ($item) {
            return $item->quantity * ($item->product->cost ?? 0);
        });

        // Purchase costs
        $purchases = Purchase::whereBetween('purchase_date', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'received')
            ->sum('total_amount');

        // Production costs
        $productionCosts = ProductionPlanItem::whereBetween('actual_end_date', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'completed')
            ->sum('actual_material_cost');

        // Calculate profit metrics
        $grossProfit = $totalRevenue - $cogs;
        $grossProfitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

        // Operating expenses (simplified - you might want to add more categories)
        $operatingExpenses = $purchases + $productionCosts;
        $netProfit = $grossProfit - $operatingExpenses;
        $netProfitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $request->period,
                'date_range' => $dateRange,
                'revenue' => [
                    'total_sales' => $totalRevenue,
                    'number_of_orders' => $sales->count(),
                ],
                'costs' => [
                    'cost_of_goods_sold' => $cogs,
                    'purchase_costs' => $purchases,
                    'production_costs' => $productionCosts,
                    'total_operating_expenses' => $operatingExpenses,
                ],
                'profit' => [
                    'gross_profit' => $grossProfit,
                    'gross_profit_margin' => round($grossProfitMargin, 2),
                    'net_profit' => $netProfit,
                    'net_profit_margin' => round($netProfitMargin, 2),
                ],
            ]
        ]);
    }

    public function productionEfficiencyReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|in:monthly,quarterly,yearly,custom',
            'date_from' => 'required_if:period,custom|date',
            'date_to' => 'required_if:period,custom|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $dateRange = $this->getDateRange($request->period, $request->date_from, $request->date_to);

        // Get completed production items
        $productionItems = ProductionPlanItem::with(['product', 'recipe', 'productionPlan'])
            ->whereBetween('actual_end_date', [$dateRange['start'], $dateRange['end']])
            ->where('status', 'completed')
            ->get();

        if ($productionItems->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'message' => 'No completed production items found for the specified period',
                    'period' => $request->period,
                    'date_range' => $dateRange,
                ]
            ]);
        }

        // Calculate efficiency metrics
        $totalPlanned = $productionItems->sum('planned_quantity');
        $totalActual = $productionItems->sum('actual_quantity');
        $overallEfficiency = $totalPlanned > 0 ? ($totalActual / $totalPlanned) * 100 : 0;

        // Cost variance
        $totalPlannedCost = $productionItems->sum('estimated_material_cost');
        $totalActualCost = $productionItems->sum('actual_material_cost');
        $costVariance = $totalActualCost - $totalPlannedCost;
        $costVariancePercentage = $totalPlannedCost > 0 ? ($costVariance / $totalPlannedCost) * 100 : 0;

        // Production by product
        $productionByProduct = $productionItems->groupBy('product_id')
            ->map(function ($items) {
                $product = $items->first()->product;
                $plannedQty = $items->sum('planned_quantity');
                $actualQty = $items->sum('actual_quantity');
                
                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'planned_quantity' => $plannedQty,
                    'actual_quantity' => $actualQty,
                    'efficiency_percentage' => $plannedQty > 0 ? round(($actualQty / $plannedQty) * 100, 2) : 0,
                    'estimated_cost' => $items->sum('estimated_material_cost'),
                    'actual_cost' => $items->sum('actual_material_cost'),
                    'cost_variance' => $items->sum('actual_material_cost') - $items->sum('estimated_material_cost'),
                ];
            })
            ->sortByDesc('actual_quantity')
            ->values();

        // On-time delivery
        $onTimeItems = $productionItems->filter(function ($item) {
            return $item->actual_end_date <= $item->planned_end_date;
        });
        $onTimePercentage = $productionItems->count() > 0 ? ($onTimeItems->count() / $productionItems->count()) * 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $request->period,
                'date_range' => $dateRange,
                'overall_metrics' => [
                    'total_production_items' => $productionItems->count(),
                    'total_planned_quantity' => $totalPlanned,
                    'total_actual_quantity' => $totalActual,
                    'overall_efficiency_percentage' => round($overallEfficiency, 2),
                    'on_time_delivery_percentage' => round($onTimePercentage, 2),
                ],
                'cost_analysis' => [
                    'total_estimated_cost' => $totalPlannedCost,
                    'total_actual_cost' => $totalActualCost,
                    'cost_variance' => $costVariance,
                    'cost_variance_percentage' => round($costVariancePercentage, 2),
                ],
                'production_by_product' => $productionByProduct,
            ]
        ]);
    }

    public function lowStockAlert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'threshold_multiplier' => 'nullable|numeric|min:0.1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $thresholdMultiplier = $request->get('threshold_multiplier', 1.0);

        // Low stock products
        $lowStockProducts = Product::with('category')
            ->where(function ($query) use ($thresholdMultiplier) {
                $query->whereColumn('quantity', '<=', DB::raw("min_quantity * {$thresholdMultiplier}"))
                      ->orWhere('quantity', '<=', 10 * $thresholdMultiplier);
            })
            ->get()
            ->map(function ($product) {
                return [
                    'type' => 'product',
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'Uncategorized',
                    'current_stock' => $product->quantity,
                    'min_stock' => $product->min_quantity,
                    'unit' => $product->unit ?? 'pcs',
                    'shortage' => max(0, $product->min_quantity - $product->quantity),
                    'days_of_stock' => $this->calculateDaysOfStock('product', $product->id, $product->quantity),
                ];
            });

        // Low stock raw materials
        $lowStockRawMaterials = RawMaterial::where(function ($query) use ($thresholdMultiplier) {
                $query->whereColumn('current_stock', '<=', DB::raw("min_stock_level * {$thresholdMultiplier}"))
                      ->orWhere('current_stock', '<=', 10 * $thresholdMultiplier);
            })
            ->get()
            ->map(function ($rawMaterial) {
                return [
                    'type' => 'raw_material',
                    'id' => $rawMaterial->id,
                    'name' => $rawMaterial->name,
                    'category' => 'Raw Materials',
                    'current_stock' => $rawMaterial->current_stock,
                    'min_stock' => $rawMaterial->min_stock_level,
                    'unit' => $rawMaterial->unit,
                    'shortage' => max(0, $rawMaterial->min_stock_level - $rawMaterial->current_stock),
                    'days_of_stock' => $this->calculateDaysOfStock('raw_material', $rawMaterial->id, $rawMaterial->current_stock),
                ];
            });

        $allLowStockItems = $lowStockProducts->concat($lowStockRawMaterials)
            ->sortBy('days_of_stock');

        return response()->json([
            'success' => true,
            'data' => [
                'threshold_multiplier' => $thresholdMultiplier,
                'summary' => [
                    'total_low_stock_items' => $allLowStockItems->count(),
                    'products' => $lowStockProducts->count(),
                    'raw_materials' => $lowStockRawMaterials->count(),
                    'critical_items' => $allLowStockItems->where('days_of_stock', '<=', 7)->count(),
                ],
                'low_stock_items' => $allLowStockItems->values(),
            ]
        ]);
    }

    private function getDateRange($period, $dateFrom = null, $dateTo = null)
    {
        $now = Carbon::now();
        
        return match($period) {
            'daily' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'weekly' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
            ],
            'monthly' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'quarterly' => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
            ],
            'yearly' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            'custom' => [
                'start' => Carbon::parse($dateFrom)->startOfDay(),
                'end' => Carbon::parse($dateTo)->endOfDay(),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
        };
    }

    private function groupSalesData($sales, $groupBy)
    {
        return match($groupBy) {
            'day' => $sales->groupBy(function ($sale) {
                return Carbon::parse($sale->sale_date)->format('Y-m-d');
            })->map(function ($daySales) {
                return [
                    'total_sales' => $daySales->sum('total_amount'),
                    'total_orders' => $daySales->count(),
                ];
            }),
            'week' => $sales->groupBy(function ($sale) {
                return Carbon::parse($sale->sale_date)->format('Y-W');
            })->map(function ($weekSales) {
                return [
                    'total_sales' => $weekSales->sum('total_amount'),
                    'total_orders' => $weekSales->count(),
                ];
            }),
            'month' => $sales->groupBy(function ($sale) {
                return Carbon::parse($sale->sale_date)->format('Y-m');
            })->map(function ($monthSales) {
                return [
                    'total_sales' => $monthSales->sum('total_amount'),
                    'total_orders' => $monthSales->count(),
                ];
            }),
            'product' => $sales->flatMap->items->groupBy('product_id')->map(function ($items) {
                $product = $items->first()->product;
                return [
                    'product_name' => $product->name,
                    'quantity_sold' => $items->sum('quantity'),
                    'total_revenue' => $items->sum(function ($item) {
                        return $item->quantity * $item->unit_price;
                    }),
                ];
            }),
            'customer' => $sales->groupBy('customer_id')->map(function ($customerSales) {
                $customer = $customerSales->first()->customer;
                return [
                    'customer_name' => $customer->name ?? 'Walk-in Customer',
                    'total_orders' => $customerSales->count(),
                    'total_spent' => $customerSales->sum('total_amount'),
                ];
            }),
            default => collect(),
        };
    }

    private function calculateDaysOfStock($type, $itemId, $currentStock)
    {
        // Calculate average daily consumption over last 30 days
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        $field = $type === 'product' ? 'product_id' : 'raw_material_id';
        
        $totalConsumed = InventoryTransaction::where($field, $itemId)
            ->where('type', 'out')
            ->where('transaction_date', '>=', $thirtyDaysAgo)
            ->sum('quantity');

        $dailyConsumption = abs($totalConsumed) / 30;
        
        return $dailyConsumption > 0 ? round($currentStock / $dailyConsumption, 1) : 999;
    }
}