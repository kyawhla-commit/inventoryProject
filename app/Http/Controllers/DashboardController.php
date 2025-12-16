<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Order;
use App\Models\Sale;
use App\Models\RawMaterial;
use App\Models\Setting;
use App\Models\Purchase;
use App\Models\Delivery;
use App\Models\ProductionPlan;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get selected month from request or default to current month
        $selectedMonth = $request->get('month', Carbon::now()->format('Y-m'));
        $selectedDate = Carbon::createFromFormat('Y-m', $selectedMonth);
        $startOfMonth = $selectedDate->copy()->startOfMonth();
        $endOfMonth = $selectedDate->copy()->endOfMonth();

        // Basic counts
        $totalProducts = Product::count();
        $totalCustomers = Customer::count();
        $totalSuppliers = Supplier::count();
        $totalSalesAmount = Sale::sum('total_amount');

        // Low stock alerts
        $lowStockProducts = Product::where('quantity', '<=', 10)->get();
        $lowStockRawMaterials = RawMaterial::whereColumn('quantity', '<=', 'minimum_stock_level')->get();

        // Order statistics
        $ordersByStatus = Order::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $totalOrders = $ordersByStatus->sum();

        // ===== MONTHLY FINANCIAL METRICS =====
        $monthlySalesGoal = Setting::get('monthly_sales_goal', 10000);
        $currentMonthSales = Sale::whereBetween('sale_date', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $salesProgressPercentage = ($monthlySalesGoal > 0) ? ($currentMonthSales / $monthlySalesGoal) * 100 : 0;

        $monthlyRevenue = $currentMonthSales;
        $monthlyPurchases = Purchase::whereBetween('purchase_date', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $monthlyStaffCosts = \App\Models\StaffDailyCharge::whereBetween('charge_date', [$startOfMonth, $endOfMonth])->sum('total_charge');
        $monthlyExpenses = $monthlyPurchases + $monthlyStaffCosts;

        // Previous month comparison
        $prevMonthStart = $selectedDate->copy()->subMonth()->startOfMonth();
        $prevMonthEnd = $selectedDate->copy()->subMonth()->endOfMonth();
        $prevMonthSales = Sale::whereBetween('sale_date', [$prevMonthStart, $prevMonthEnd])->sum('total_amount');
        $prevMonthPurchases = Purchase::whereBetween('purchase_date', [$prevMonthStart, $prevMonthEnd])->sum('total_amount');
        $prevMonthStaffCosts = \App\Models\StaffDailyCharge::whereBetween('charge_date', [$prevMonthStart, $prevMonthEnd])->sum('total_charge');
        $prevMonthExpenses = $prevMonthPurchases + $prevMonthStaffCosts;

        $monthlyRevenueChange = $prevMonthSales > 0 ? (($monthlyRevenue - $prevMonthSales) / $prevMonthSales) * 100 : 0;
        $monthlyExpenseChange = $prevMonthExpenses > 0 ? (($monthlyExpenses - $prevMonthExpenses) / $prevMonthExpenses) * 100 : 0;

        $monthlyProfit = $monthlyRevenue - $monthlyExpenses;
        $prevMonthProfit = $prevMonthSales - $prevMonthExpenses;
        $monthlyProfitChange = $prevMonthProfit != 0 ? (($monthlyProfit - $prevMonthProfit) / abs($prevMonthProfit)) * 100 : 0;

        // ===== TODAY'S METRICS =====
        $today = Carbon::today();
        $todayStats = [
            'sales' => Sale::whereDate('sale_date', $today)->sum('total_amount'),
            'sales_count' => Sale::whereDate('sale_date', $today)->count(),
            'orders' => Order::whereDate('order_date', $today)->count(),
            'new_customers' => Customer::whereDate('created_at', $today)->count(),
        ];

        // ===== DELIVERY METRICS =====
        $deliveryStats = [
            'total' => Delivery::count(),
            'pending' => Delivery::where('status', 'pending')->count(),
            'in_progress' => Delivery::whereIn('status', ['assigned', 'picked_up', 'in_transit'])->count(),
            'today_scheduled' => Delivery::whereDate('scheduled_date', $today)->count(),
            'today_delivered' => Delivery::whereDate('scheduled_date', $today)->where('status', 'delivered')->count(),
            'today_pending' => Delivery::whereDate('scheduled_date', $today)->whereIn('status', ['pending', 'assigned'])->count(),
        ];

        // Today's deliveries list
        $todayDeliveries = Delivery::with(['order', 'customer'])
            ->whereDate('scheduled_date', $today)
            ->orderBy('scheduled_time')
            ->limit(5)
            ->get();

        // ===== PRODUCTION METRICS =====
        $productionStats = [
            'active_plans' => ProductionPlan::whereIn('status', ['planned', 'in_progress'])->count(),
            'completed_today' => ProductionPlan::whereDate('updated_at', $today)->where('status', 'completed')->count(),
            'pending_plans' => ProductionPlan::where('status', 'planned')->count(),
        ];

        // ===== PURCHASE METRICS =====
        $purchaseStats = [
            'pending' => Purchase::where('status', 'pending')->count(),
            'approved' => Purchase::where('status', 'approved')->count(),
            'this_month_total' => $monthlyPurchases,
            'pending_value' => Purchase::where('status', 'pending')->sum('total_amount'),
        ];

        // ===== INVENTORY VALUE =====
        $inventoryValue = [
            'products' => Product::sum(DB::raw('quantity * COALESCE(cost, 0)')),
            'raw_materials' => RawMaterial::sum(DB::raw('quantity * cost_per_unit')),
        ];
        $inventoryValue['total'] = $inventoryValue['products'] + $inventoryValue['raw_materials'];

        // ===== RECENT ACTIVITIES =====
        $recentSales = Sale::with('customer')->latest()->take(5)->get();
        $recentOrders = Order::with('customer')->latest()->take(5)->get();
        $pendingDeliveries = Delivery::with(['order', 'customer'])
            ->where('status', 'pending')
            ->orderBy('scheduled_date')
            ->limit(5)
            ->get();

        // ===== CHARTS DATA =====
        // Monthly sales & purchases (last 12 months)
        $months = collect(range(0, 11))->map(function ($i) {
            return Carbon::now()->subMonths($i)->format('Y-m');
        })->reverse();

        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        if (DB::connection()->getDriverName() === 'sqlite') {
            $salesPerMonth = Sale::selectRaw('strftime("%Y-%m", sale_date) as ym, SUM(total_amount) as total')
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->groupBy('ym')
                ->pluck('total', 'ym');

            $purchasesPerMonth = Purchase::selectRaw('strftime("%Y-%m", purchase_date) as ym, SUM(total_amount) as total')
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->groupBy('ym')
                ->pluck('total', 'ym');
        } else {
            $salesPerMonth = Sale::selectRaw('DATE_FORMAT(sale_date, "%Y-%m") as ym, SUM(total_amount) as total')
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->groupBy('ym')
                ->pluck('total', 'ym');

            $purchasesPerMonth = Purchase::selectRaw('DATE_FORMAT(purchase_date, "%Y-%m") as ym, SUM(total_amount) as total')
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->groupBy('ym')
                ->pluck('total', 'ym');
        }

        $salesTotals = $months->map(fn($m) => (float) ($salesPerMonth[$m] ?? 0))->values();
        $purchaseTotals = $months->map(fn($m) => (float) ($purchasesPerMonth[$m] ?? 0))->values();

        // Top selling products (top 5)
        $topProductsData = DB::table('sale_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(quantity * unit_price) as total_revenue'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $topProductNames = Product::whereIn('id', $topProductsData->pluck('product_id'))
            ->pluck('name', 'id');

        $topProductLabels = $topProductsData->map(fn($row) => $topProductNames[$row->product_id] ?? '')->values();
        $topProductQuantities = $topProductsData->map(fn($row) => (int) $row->total_qty)->values();

        // ===== QUICK ACTIONS DATA =====
        $quickActions = [
            'low_stock_count' => $lowStockProducts->count() + $lowStockRawMaterials->count(),
            'pending_orders' => $ordersByStatus['pending'] ?? 0,
            'pending_deliveries' => $deliveryStats['pending'],
            'pending_purchases' => $purchaseStats['pending'],
        ];

        return view('dashboard.index', compact(
            'totalProducts',
            'totalCustomers',
            'totalSuppliers',
            'totalSalesAmount',
            'lowStockProducts',
            'lowStockRawMaterials',
            'recentSales',
            'recentOrders',
            'monthlySalesGoal',
            'currentMonthSales',
            'salesProgressPercentage',
            'ordersByStatus',
            'totalOrders',
            'months',
            'salesTotals',
            'purchaseTotals',
            'topProductLabels',
            'topProductQuantities',
            'monthlyExpenses',
            'monthlyExpenseChange',
            'monthlyRevenue',
            'monthlyRevenueChange',
            'monthlyProfit',
            'monthlyProfitChange',
            'selectedMonth',
            'todayStats',
            'deliveryStats',
            'todayDeliveries',
            'pendingDeliveries',
            'productionStats',
            'purchaseStats',
            'inventoryValue',
            'quickActions'
        ));
    }

    public function updateGoal(Request $request)
    {
        $request->validate([
            'monthly_sales_goal' => 'required|numeric|min:1',
        ]);

        Setting::set('monthly_sales_goal', $request->monthly_sales_goal);

        return redirect()->back()->with('success', 'Monthly sales goal updated');
    }
}
