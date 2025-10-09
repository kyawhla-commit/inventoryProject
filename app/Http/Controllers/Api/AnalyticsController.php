<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\ProductionPlanItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function salesTrends(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'required|in:7days,30days,90days,1year',
            'metric' => 'nullable|in:revenue,orders,average_order_value',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $days = match($request->period) {
            '7days' => 7,
            '30days' => 30,
            '90days' => 90,
            '1year' => 365,
            default => 30,
        };

        $startDate = Carbon::now()->subDays($days);
        $metric = $request->get('metric', 'revenue');

        // Get sales data
        $sales = Sale::whereBetween('sale_date', [$startDate, Carbon::now()])
            ->orderBy('sale_date')
            ->get();

        // Group by day
        $dailyData = $sales->groupBy(function ($sale) {
            return Carbon::parse($sale->sale_date)->format('Y-m-d');
        })->map(function ($daySales) use ($metric) {
            $revenue = $daySales->sum('total_amount');
            $orders = $daySales->count();
            
            return [
                'date' => $daySales->first()->sale_date,
                'revenue' => $revenue,
                'orders' => $orders,
                'average_order_value' => $orders > 0 ? $revenue / $orders : 0,
            ];
        })->values();

        // Calculate trend (percentage change from first to last period)
        $firstPeriodValue = $dailyData->take(7)->avg($metric);
        $lastPeriodValue = $dailyData->slice(-7)->avg($metric);
        $trendPercentage = $firstPeriodValue > 0 ? (($lastPeriodValue - $firstPeriodValue) / $firstPeriodValue) * 100 : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $request->period,
                'metric' => $metric,
                'trend_percentage' => round($trendPercentage, 2),
                'trend_direction' => $trendPercentage > 0 ? 'up' : ($trendPercentage < 0 ? 'down' : 'stable'),
                'daily_data' => $dailyData,
                'summary' => [
                    'total_revenue' => $sales->sum('total_amount'),
                    'total_orders' => $sales->count(),
                    'average_daily_revenue' => $dailyData->avg('revenue'),
                    'average_daily_orders' => $dailyData->avg('orders'),
                ],
            ]
        ]);
    }

    public function productPerformance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:30days,90days,1year',
            'limit' => 'nullable|integer|min:5|max:50',
            'sort_by' => 'nullable|in:revenue,quantity,profit_margin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $days = match($request->get('period', '30days')) {
            '30days' => 30,
            '90days' => 90,
            '1year' => 365,
            default => 30,
        };

        $limit = $request->get('limit', 20);
        $sortBy = $request->get('sort_by', 'revenue');
        $startDate = Carbon::now()->subDays($days);

        // Get sales data with product information
        $salesItems = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.sale_date', '>=', $startDate)
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.cost as product_cost',
                'categories.name as category_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as total_revenue'),
                DB::raw('SUM(sale_items.quantity * products.cost) as total_cost'),
                DB::raw('COUNT(DISTINCT sales.id) as order_frequency')
            )
            ->groupBy('products.id', 'products.name', 'products.cost', 'categories.name')
            ->get();

        // Calculate performance metrics
        $productPerformance = $salesItems->map(function ($item) {
            $profitMargin = $item->total_revenue > 0 ? (($item->total_revenue - $item->total_cost) / $item->total_revenue) * 100 : 0;
            
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'category' => $item->category_name ?? 'Uncategorized',
                'total_quantity' => $item->total_quantity,
                'total_revenue' => $item->total_revenue,
                'total_cost' => $item->total_cost,
                'profit' => $item->total_revenue - $item->total_cost,
                'profit_margin' => round($profitMargin, 2),
                'order_frequency' => $item->order_frequency,
                'average_selling_price' => $item->total_quantity > 0 ? $item->total_revenue / $item->total_quantity : 0,
            ];
        });

        // Sort by requested metric
        $sortedProducts = $productPerformance->sortByDesc($sortBy)->take($limit)->values();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $request->get('period', '30days'),
                'sort_by' => $sortBy,
                'products' => $sortedProducts,
                'summary' => [
                    'total_products_sold' => $productPerformance->count(),
                    'total_revenue' => $productPerformance->sum('total_revenue'),
                    'total_profit' => $productPerformance->sum('profit'),
                    'average_profit_margin' => $productPerformance->avg('profit_margin'),
                ],
            ]
        ]);
    }

    public function customerAnalytics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:30days,90days,1year',
            'segment_by' => 'nullable|in:value,frequency,recency',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $days = match($request->get('period', '90days')) {
            '30days' => 30,
            '90days' => 90,
            '1year' => 365,
            default => 90,
        };

        $startDate = Carbon::now()->subDays($days);

        // Get customer data
        $customerData = Customer::leftJoin('sales', 'customers.id', '=', 'sales.customer_id')
            ->where(function ($query) use ($startDate) {
                $query->where('sales.sale_date', '>=', $startDate)
                      ->orWhereNull('sales.id');
            })
            ->select(
                'customers.id',
                'customers.name',
                'customers.email',
                DB::raw('COUNT(sales.id) as total_orders'),
                DB::raw('SUM(sales.total_amount) as total_spent'),
                DB::raw('MAX(sales.sale_date) as last_purchase_date'),
                DB::raw('MIN(sales.sale_date) as first_purchase_date')
            )
            ->groupBy('customers.id', 'customers.name', 'customers.email')
            ->get();

        // Calculate customer metrics
        $customerAnalytics = $customerData->map(function ($customer) {
            $daysSinceLastPurchase = $customer->last_purchase_date 
                ? Carbon::parse($customer->last_purchase_date)->diffInDays(Carbon::now())
                : null;
            
            $averageOrderValue = $customer->total_orders > 0 ? $customer->total_spent / $customer->total_orders : 0;
            
            return [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'total_orders' => $customer->total_orders,
                'total_spent' => $customer->total_spent,
                'average_order_value' => round($averageOrderValue, 2),
                'last_purchase_date' => $customer->last_purchase_date,
                'days_since_last_purchase' => $daysSinceLastPurchase,
                'customer_lifetime_days' => $customer->first_purchase_date && $customer->last_purchase_date
                    ? Carbon::parse($customer->first_purchase_date)->diffInDays(Carbon::parse($customer->last_purchase_date))
                    : 0,
            ];
        });

        // Customer segmentation
        $segments = [
            'high_value' => $customerAnalytics->where('total_spent', '>', 1000)->count(),
            'medium_value' => $customerAnalytics->whereBetween('total_spent', [500, 1000])->count(),
            'low_value' => $customerAnalytics->where('total_spent', '<', 500)->count(),
            'frequent_buyers' => $customerAnalytics->where('total_orders', '>', 5)->count(),
            'recent_customers' => $customerAnalytics->where('days_since_last_purchase', '<=', 30)->count(),
            'at_risk_customers' => $customerAnalytics->where('days_since_last_purchase', '>', 90)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $request->get('period', '90days'),
                'customers' => $customerAnalytics->sortByDesc('total_spent')->take(50)->values(),
                'segments' => $segments,
                'summary' => [
                    'total_customers' => $customerAnalytics->count(),
                    'active_customers' => $customerAnalytics->where('total_orders', '>', 0)->count(),
                    'average_customer_value' => $customerAnalytics->avg('total_spent'),
                    'average_order_frequency' => $customerAnalytics->avg('total_orders'),
                ],
            ]
        ]);
    }

    public function inventoryTurnover(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period' => 'nullable|in:monthly,quarterly,yearly',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $period = $request->get('period', 'monthly');
        $days = match($period) {
            'monthly' => 30,
            'quarterly' => 90,
            'yearly' => 365,
            default => 30,
        };

        $startDate = Carbon::now()->subDays($days);

        // Build query for products
        $query = Product::with('category');
        
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->get();

        // Calculate turnover for each product
        $inventoryTurnover = $products->map(function ($product) use ($startDate, $days) {
            // Get total quantity sold in the period
            $totalSold = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sale_items.product_id', $product->id)
                ->where('sales.sale_date', '>=', $startDate)
                ->sum('sale_items.quantity');

            // Calculate average inventory (simplified - using current stock)
            $averageInventory = $product->quantity > 0 ? $product->quantity : 1;
            
            // Calculate turnover ratio
            $turnoverRatio = $averageInventory > 0 ? $totalSold / $averageInventory : 0;
            
            // Calculate days in inventory
            $daysInInventory = $turnoverRatio > 0 ? $days / $turnoverRatio : $days;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'category' => $product->category->name ?? 'Uncategorized',
                'current_stock' => $product->quantity,
                'total_sold' => $totalSold,
                'turnover_ratio' => round($turnoverRatio, 2),
                'days_in_inventory' => round($daysInInventory, 1),
                'turnover_category' => $this->categorizeTurnover($turnoverRatio, $days),
            ];
        });

        // Sort by turnover ratio
        $sortedTurnover = $inventoryTurnover->sortByDesc('turnover_ratio')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'products' => $sortedTurnover,
                'summary' => [
                    'average_turnover_ratio' => $inventoryTurnover->avg('turnover_ratio'),
                    'fast_moving_items' => $inventoryTurnover->where('turnover_category', 'fast')->count(),
                    'slow_moving_items' => $inventoryTurnover->where('turnover_category', 'slow')->count(),
                    'dead_stock_items' => $inventoryTurnover->where('turnover_category', 'dead')->count(),
                ],
            ]
        ]);
    }

    public function forecastDemand(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'forecast_days' => 'nullable|integer|min:7|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $productId = $request->product_id;
        $forecastDays = $request->get('forecast_days', 30);
        
        // Get historical sales data (last 90 days)
        $historicalDays = 90;
        $startDate = Carbon::now()->subDays($historicalDays);
        
        $historicalSales = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sale_items.product_id', $productId)
            ->where('sales.sale_date', '>=', $startDate)
            ->select(
                DB::raw('DATE(sales.sale_date) as sale_date'),
                DB::raw('SUM(sale_items.quantity) as daily_quantity')
            )
            ->groupBy(DB::raw('DATE(sales.sale_date)'))
            ->orderBy('sale_date')
            ->get();

        if ($historicalSales->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'message' => 'No historical sales data found for this product',
                    'product_id' => $productId,
                    'forecast_days' => $forecastDays,
                ]
            ]);
        }

        // Simple moving average forecast
        $dailyQuantities = $historicalSales->pluck('daily_quantity')->toArray();
        $averageDailyDemand = array_sum($dailyQuantities) / count($dailyQuantities);
        
        // Calculate trend (simple linear trend)
        $trend = $this->calculateTrend($dailyQuantities);
        
        // Generate forecast
        $forecast = [];
        for ($i = 1; $i <= $forecastDays; $i++) {
            $forecastedDemand = max(0, $averageDailyDemand + ($trend * $i));
            $forecast[] = [
                'date' => Carbon::now()->addDays($i)->format('Y-m-d'),
                'forecasted_demand' => round($forecastedDemand, 2),
            ];
        }

        $totalForecastedDemand = array_sum(array_column($forecast, 'forecasted_demand'));
        
        // Get current stock
        $product = Product::find($productId);
        $currentStock = $product->quantity;
        
        // Calculate when stock will run out
        $stockoutDate = null;
        $runningStock = $currentStock;
        foreach ($forecast as $day) {
            $runningStock -= $day['forecasted_demand'];
            if ($runningStock <= 0 && !$stockoutDate) {
                $stockoutDate = $day['date'];
                break;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $productId,
                'product_name' => $product->name,
                'forecast_period' => $forecastDays,
                'current_stock' => $currentStock,
                'historical_analysis' => [
                    'days_analyzed' => count($dailyQuantities),
                    'average_daily_demand' => round($averageDailyDemand, 2),
                    'trend' => round($trend, 4),
                    'total_historical_demand' => array_sum($dailyQuantities),
                ],
                'forecast' => $forecast,
                'forecast_summary' => [
                    'total_forecasted_demand' => round($totalForecastedDemand, 2),
                    'average_daily_forecast' => round($totalForecastedDemand / $forecastDays, 2),
                    'estimated_stockout_date' => $stockoutDate,
                    'recommended_reorder_quantity' => max(0, round($totalForecastedDemand - $currentStock, 2)),
                ],
            ]
        ]);
    }

    private function categorizeTurnover($turnoverRatio, $periodDays)
    {
        $annualizedRatio = $turnoverRatio * (365 / $periodDays);
        
        if ($annualizedRatio >= 12) return 'fast';
        if ($annualizedRatio >= 4) return 'medium';
        if ($annualizedRatio >= 1) return 'slow';
        return 'dead';
    }

    private function calculateTrend($data)
    {
        $n = count($data);
        if ($n < 2) return 0;
        
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $data[$i];
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        return $slope;
    }
}