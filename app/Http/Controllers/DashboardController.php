<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Order;
use App\Models\Sale;
use App\Models\RawMaterial;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Purchase;
use App\Models\SaleItem;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalProducts = Product::count();
        $totalCustomers = Customer::count();
        $totalSuppliers = Supplier::count();
        $totalSalesAmount = Sale::sum('total_amount');
        $lowStockProducts = Product::where('quantity', '<=', 10)->get();
        $lowStockRawMaterials = RawMaterial::whereColumn('quantity', '<=', 'minimum_stock_level')->get();

        // Order statistics
        $ordersByStatus = Order::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $totalOrders = $ordersByStatus->sum();

        // Get selected month from request or default to current month
        $selectedMonth = $request->get('month', Carbon::now()->format('Y-m'));
        $selectedDate = Carbon::createFromFormat('Y-m', $selectedMonth);
        $startOfMonth = $selectedDate->copy()->startOfMonth();
        $endOfMonth = $selectedDate->copy()->endOfMonth();

        // Monthly Sales Goal
        $monthlySalesGoal = Setting::get('monthly_sales_goal', 10000);
        $currentMonthSales = Sale::whereBetween('sale_date', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $salesProgressPercentage = ($monthlySalesGoal > 0) ? ($currentMonthSales / $monthlySalesGoal) * 100 : 0;

        // Monthly Revenue (same as sales for this context)
        $monthlyRevenue = $currentMonthSales;

        // Monthly Expenses Calculation
        $monthlyPurchases = Purchase::whereBetween('purchase_date', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $monthlyStaffCosts = \App\Models\StaffDailyCharge::whereBetween('charge_date', [$startOfMonth, $endOfMonth])->sum('total_charge');
        $monthlyExpenses = $monthlyPurchases + $monthlyStaffCosts;

        // Previous month for comparison
        $prevMonthStart = $selectedDate->copy()->subMonth()->startOfMonth();
        $prevMonthEnd = $selectedDate->copy()->subMonth()->endOfMonth();
        $prevMonthSales = Sale::whereBetween('sale_date', [$prevMonthStart, $prevMonthEnd])->sum('total_amount');
        $prevMonthPurchases = Purchase::whereBetween('purchase_date', [$prevMonthStart, $prevMonthEnd])->sum('total_amount');
        $prevMonthStaffCosts = \App\Models\StaffDailyCharge::whereBetween('charge_date', [$prevMonthStart, $prevMonthEnd])->sum('total_charge');
        $prevMonthExpenses = $prevMonthPurchases + $prevMonthStaffCosts;

        // Calculate percentage changes
        $monthlyRevenueChange = $prevMonthSales > 0 ? (($monthlyRevenue - $prevMonthSales) / $prevMonthSales) * 100 : 0;
        $monthlyExpenseChange = $prevMonthExpenses > 0 ? (($monthlyExpenses - $prevMonthExpenses) / $prevMonthExpenses) * 100 : 0;

        // Monthly Profit
        $monthlyProfit = $monthlyRevenue - $monthlyExpenses;
        $prevMonthProfit = $prevMonthSales - $prevMonthExpenses;
        $monthlyProfitChange = $prevMonthProfit != 0 ? (($monthlyProfit - $prevMonthProfit) / abs($prevMonthProfit)) * 100 : 0;

        $recentSales = Sale::with('customer')->latest()->take(10)->get();

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
        } else {
            $salesPerMonth = Sale::selectRaw('DATE_FORMAT(sale_date, "%Y-%m") as ym, SUM(total_amount) as total')
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->groupBy('ym')
                ->pluck('total', 'ym');
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            $purchasesPerMonth = Purchase::selectRaw('strftime("%Y-%m", purchase_date) as ym, SUM(total_amount) as total')
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->groupBy('ym')
                ->pluck('total', 'ym');
        } else {
            $purchasesPerMonth = Purchase::selectRaw('DATE_FORMAT(purchase_date, "%Y-%m") as ym, SUM(total_amount) as total')
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->groupBy('ym')
                ->pluck('total', 'ym');
        }

        $salesTotals = $months->map(fn($m) => (float) ($salesPerMonth[$m] ?? 0))->values();
        $purchaseTotals = $months->map(fn($m) => (float) ($purchasesPerMonth[$m] ?? 0))->values();

        // Top selling products (top 5)
        $topProductsData = DB::table('sale_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $topProductNames = Product::whereIn('id', $topProductsData->pluck('product_id'))
            ->pluck('name', 'id');

        $topProductLabels = $topProductsData->map(fn($row) => $topProductNames[$row->product_id] ?? '')->values();
        $topProductQuantities = $topProductsData->map(fn($row) => (int) $row->total_qty)->values();

        return view('dashboard.index', compact(
            'totalProducts',
            'totalCustomers',
            'totalSuppliers',
            'totalSalesAmount',
            'lowStockProducts',
            'lowStockRawMaterials',
            'recentSales',
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
            'selectedMonth'
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
