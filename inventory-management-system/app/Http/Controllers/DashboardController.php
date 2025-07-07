<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\RawMaterial;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();
        $totalCustomers = Customer::count();
        $totalSuppliers = Supplier::count();
        $totalSalesAmount = Sale::sum('total_amount');
        $lowStockProducts = Product::where('quantity', '<=', 10)->get();
        $lowStockRawMaterials = RawMaterial::whereColumn('quantity', '<=', 'minimum_stock_level')->get();

        // Monthly Sales Goal
        $monthlySalesGoal = 10000; // Set your monthly sales goal here
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $currentMonthSales = Sale::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $salesProgressPercentage = ($monthlySalesGoal > 0) ? ($currentMonthSales / $monthlySalesGoal) * 100 : 0;

        $recentSales = Sale::with('customer')->latest()->take(10)->get();

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
            'salesProgressPercentage'
        ));
    }
}

