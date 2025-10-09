<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function overview()
    {
        $data = [
            'totals' => [
                'products' => Product::count(),
                'categories' => Category::count(),
                'raw_materials' => RawMaterial::count(),
                'suppliers' => Supplier::count(),
            ],
            'inventory_alerts' => [
                'low_stock_products' => Product::whereColumn('quantity', '<=', 'min_quantity')->count(),
                'low_stock_raw_materials' => RawMaterial::whereColumn('current_stock', '<=', 'min_stock_level')->count(),
                'out_of_stock_products' => Product::where('quantity', 0)->count(),
            ],
            'recent_activity' => [
                'recent_sales' => Sale::with(['customer'])
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(function($sale) {
                        return [
                            'id' => $sale->id,
                            'customer' => $sale->customer->name ?? 'Walk-in Customer',
                            'total' => $sale->total_amount,
                            'date' => $sale->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                'recent_purchases' => Purchase::with(['supplier'])
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(function($purchase) {
                        return [
                            'id' => $purchase->id,
                            'supplier' => $purchase->supplier->name ?? 'Unknown Supplier',
                            'total' => $purchase->total_amount,
                            'date' => $purchase->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function salesStats(Request $request)
    {
        $period = $request->get('period', 'month'); // day, week, month, year
        
        $startDate = match($period) {
            'day' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $sales = Sale::where('created_at', '>=', $startDate)->get();
        
        $stats = [
            'total_sales' => $sales->sum('total_amount'),
            'total_orders' => $sales->count(),
            'average_order_value' => $sales->count() > 0 ? $sales->sum('total_amount') / $sales->count() : 0,
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function inventoryValue()
    {
        $productValue = Product::selectRaw('SUM(quantity * cost) as total_value')->first()->total_value ?? 0;
        $rawMaterialValue = RawMaterial::selectRaw('SUM(current_stock * cost_per_unit) as total_value')->first()->total_value ?? 0;
        
        $data = [
            'product_inventory_value' => (float) $productValue,
            'raw_material_inventory_value' => (float) $rawMaterialValue,
            'total_inventory_value' => (float) ($productValue + $rawMaterialValue),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // New: Detailed low/out-of-stock lists for dashboard alerts
    public function inventoryAlertsDetail()
    {
        $lowStockProducts = Product::whereColumn('quantity', '<=', 'min_quantity')
            ->orderBy('quantity')
            ->take(50)
            ->get(['id', 'name', 'quantity', 'min_quantity']);

        $outOfStockProducts = Product::where('quantity', 0)
            ->orderBy('name')
            ->take(50)
            ->get(['id', 'name', 'quantity']);

        $lowStockRawMaterials = RawMaterial::whereColumn('current_stock', '<=', 'min_stock_level')
            ->orderBy('current_stock')
            ->take(50)
            ->get(['id', 'name', 'current_stock', 'min_stock_level']);

        return response()->json([
            'success' => true,
            'data' => [
                'low_stock_products' => $lowStockProducts,
                'out_of_stock_products' => $outOfStockProducts,
                'low_stock_raw_materials' => $lowStockRawMaterials,
            ],
        ]);
    }

    // New: Sales trend aggregated by day within a period
    public function salesTrend(Request $request)
    {
        $period = $request->get('period', 'month'); // day, week, month, year
        $startDate = match($period) {
            'day' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $rows = Sale::selectRaw("DATE(created_at) as d, SUM(total_amount) as total, COUNT(*) as orders")
            ->where('created_at', '>=', $startDate)
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => [ 'period' => $period, 'start_date' => $startDate->format('Y-m-d') ],
        ]);
    }

    // New: Purchases trend aggregated by day within a period
    public function purchasesTrend(Request $request)
    {
        $period = $request->get('period', 'month');
        $startDate = match($period) {
            'day' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $rows = Purchase::selectRaw("DATE(created_at) as d, SUM(total_amount) as total, COUNT(*) as orders")
            ->where('created_at', '>=', $startDate)
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => [ 'period' => $period, 'start_date' => $startDate->format('Y-m-d') ],
        ]);
    }

    // New: Products per category breakdown
    public function categoryBreakdown()
    {
        $rows = Category::leftJoin('products', 'products.category_id', '=', 'categories.id')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
            ->get([
                'categories.id',
                'categories.name',
                DB::raw('COUNT(products.id) as products_count')
            ]);

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    // New: Supplier purchase totals within a period
    public function supplierStats(Request $request)
    {
        $period = $request->get('period', 'month');
        $startDate = match($period) {
            'day' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $rows = Supplier::leftJoin('purchases', 'purchases.supplier_id', '=', 'suppliers.id')
            ->where(function ($q) use ($startDate) {
                $q->whereNull('purchases.created_at')->orWhere('purchases.created_at', '>=', $startDate);
            })
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc(DB::raw('COALESCE(SUM(purchases.total_amount),0)'))
            ->take(20)
            ->get([
                'suppliers.id',
                'suppliers.name',
                DB::raw('COALESCE(SUM(purchases.total_amount),0) as total_purchased'),
                DB::raw('COUNT(purchases.id) as orders'),
            ]);

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => [ 'period' => $period, 'start_date' => $startDate->format('Y-m-d') ],
        ]);
    }

    // New: Simple profit estimate (Revenue - Purchases) in a period
    public function profitEstimate(Request $request)
    {
        $period = $request->get('period', 'month');

        // Support explicit monthly selection via year + month params
        if ($request->filled('month')) {
            $month = (int) $request->get('month');
            $year = (int) ($request->get('year', Carbon::now()->year));
            // Clamp month into 1..12
            if ($month < 1 || $month > 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid month. Use 1-12.'
                ], 422);
            }
            $startDate = Carbon::create($year, $month, 1, 0, 0, 0)->startOfMonth();
            $endDate = (clone $startDate)->endOfMonth();
            $meta = [
                'mode' => 'monthly_specific',
                'year' => $year,
                'month' => $month,
            ];
        } else {
            // Default behaviour: derive start based on period and use now as end
            $startDate = match($period) {
                'day' => Carbon::today(),
                'week' => Carbon::now()->startOfWeek(),
                'month' => Carbon::now()->startOfMonth(),
                'year' => Carbon::now()->startOfYear(),
                default => Carbon::now()->startOfMonth(),
            };
            $endDate = Carbon::now();
            $meta = [ 'mode' => 'period', 'period' => $period ];
        }

        // Use business date columns if available (sale_date/purchase_date),
        // fallback to created_at for compatibility
        $revenue = (float) (
            Sale::whereBetween(DB::raw("COALESCE(sale_date, created_at)"), [$startDate, $endDate])
                ->sum('total_amount')
        );
        $purchases = (float) (
            Purchase::whereBetween(DB::raw("COALESCE(purchase_date, created_at)"), [$startDate, $endDate])
                ->sum('total_amount')
        );

        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'revenue' => $revenue,
                'purchases' => $purchases,
                'profit_estimate' => $revenue - $purchases,
            ],
            'meta' => $meta,
        ]);
    }

    // New: Monthly history for the past N months (sales and purchases per month)
    public function monthlyHistory(Request $request)
    {
        $months = (int) $request->get('months', 12);
        if ($months < 1 || $months > 24) { $months = 12; }

        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);

        $sales = Sale::selectRaw("DATE_FORMAT(COALESCE(sale_date, created_at), '%Y-%m') as ym, SUM(total_amount) as total")
            ->whereRaw('COALESCE(sale_date, created_at) >= ?', [$start])
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()->keyBy('ym');

        $purchases = Purchase::selectRaw("DATE_FORMAT(COALESCE(purchase_date, created_at), '%Y-%m') as ym, SUM(total_amount) as total")
            ->whereRaw('COALESCE(purchase_date, created_at) >= ?', [$start])
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()->keyBy('ym');

        $series = [];
        $cursor = $start->copy();
        for ($i = 0; $i < $months; $i++) {
            $ym = $cursor->format('Y-m');
            $series[] = [
                'ym' => $ym,
                'sales' => (float) ($sales[$ym]->total ?? 0),
                'purchases' => (float) ($purchases[$ym]->total ?? 0),
            ];
            $cursor->addMonth();
        }

        return response()->json([
            'success' => true,
            'data' => $series,
            'meta' => [ 'start' => $start->format('Y-m-01'), 'months' => $months ],
        ]);
    }

    // New: Top seller products for a specific month (by quantity and revenue)
    public function topSellers(Request $request)
    {
        $month = (int) $request->get('month', Carbon::now()->month);
        $year = (int) $request->get('year', Carbon::now()->year);
        $limit = (int) $request->get('limit', 10);
        if ($limit < 1 || $limit > 50) { $limit = 10; }

        if ($month < 1 || $month > 12) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid month. Use 1-12.'
            ], 422);
        }

        $start = Carbon::create($year, $month, 1, 0, 0, 0)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        // Assuming sale_items table: sale_id, product_id, quantity, unit_price
        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.created_at', [$start, $end])
            ->groupBy('sale_items.product_id', 'products.name')
            ->orderByDesc(DB::raw('SUM(sale_items.quantity)'))
            ->limit($limit)
            ->get([
                'sale_items.product_id as product_id',
                'products.name as name',
                DB::raw('SUM(sale_items.quantity) as qty'),
                DB::raw('SUM(sale_items.quantity * sale_items.unit_price) as revenue')
            ]);

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => [ 'year' => $year, 'month' => $month ],
        ]);
    }

    // New: Order status summary counts (optionally for a period)
    public function orderStatusSummary(Request $request)
    {
        $period = $request->get('period'); // optional day|week|month|year
        if ($period) {
            $start = match($period) {
                'day' => Carbon::today(),
                'week' => Carbon::now()->startOfWeek(),
                'month' => Carbon::now()->startOfMonth(),
                'year' => Carbon::now()->startOfYear(),
                default => Carbon::now()->startOfMonth(),
            };
            $query = Order::where('created_at', '>=', $start);
        } else {
            $query = Order::query();
        }

        $rows = $query->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get();

        $summary = [
            'pending' => 0,
            'processing' => 0,
            'shipped' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];
        foreach ($rows as $r) {
            $key = strtolower((string)$r->status);
            if (!array_key_exists($key, $summary)) $summary[$key] = 0;
            $summary[$key] = (int) $r->total;
        }

        return response()->json([
            'success' => true,
            'data' => $summary,
            'meta' => $period ? [ 'period' => $period, 'start_date' => $start->format('Y-m-d') ] : null,
        ]);
    }
}