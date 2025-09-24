<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StaffDailyChargeController;
use App\Http\Controllers\ProfitLossController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\RawMaterialUsageController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\ProductionReportController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ProductRawMaterialController;
use Illuminate\Support\Facades\Auth;

Auth::routes();

// Language switcher
Route::get('lang/{locale}', function ($locale) {
    if (! in_array($locale, ['en', 'mm'])) {
        abort(400);
    }
    session(['locale' => $locale]);
    return redirect()->back()->withCookie(cookie('locale', $locale, 60*24*365)); // 1 year
})->name('lang.switch');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/goal', [DashboardController::class, 'updateGoal'])->name('dashboard.goal');

    Route::middleware('role:admin,staff')->group(function () {
        Route::resource('products', ProductController::class);
        
        // Product Raw Material Relationships
        Route::get('products/{product}/raw-materials', [ProductRawMaterialController::class, 'index'])->name('products.raw-materials.index');
        Route::get('products/{product}/raw-materials/create', [ProductRawMaterialController::class, 'create'])->name('products.raw-materials.create');
        Route::post('products/{product}/raw-materials', [ProductRawMaterialController::class, 'store'])->name('products.raw-materials.store');
        Route::get('products/{product}/raw-materials/{rawMaterial}/edit', [ProductRawMaterialController::class, 'edit'])->name('products.raw-materials.edit');
        Route::put('products/{product}/raw-materials/{rawMaterial}', [ProductRawMaterialController::class, 'update'])->name('products.raw-materials.update');
        Route::delete('products/{product}/raw-materials/{rawMaterial}', [ProductRawMaterialController::class, 'destroy'])->name('products.raw-materials.destroy');
        Route::get('products/{product}/calculate-cost', [ProductRawMaterialController::class, 'calculateCost'])->name('products.calculate-cost');
        Route::post('products/{product}/raw-materials/bulk-add', [ProductRawMaterialController::class, 'bulkAdd'])->name('products.raw-materials.bulk-add');
        Route::resource('purchases', PurchaseController::class);
        Route::resource('sales', SaleController::class);
        Route::resource('customers', CustomerController::class);
        Route::resource('raw-materials', RawMaterialController::class);
        Route::get('raw-materials-low-stock', [RawMaterialController::class, 'lowStock'])->name('raw-materials.low-stock');
        
        // Raw Material Usage Tracking
        Route::resource('raw-material-usages', RawMaterialUsageController::class);
        Route::get('raw-material-usages-bulk/create', [RawMaterialUsageController::class, 'bulkCreate'])->name('raw-material-usages.bulk-create');
        Route::post('raw-material-usages-bulk/store', [RawMaterialUsageController::class, 'bulkStore'])->name('raw-material-usages.bulk-store');
        Route::get('raw-materials/{rawMaterial}/usage-stats', [RawMaterialUsageController::class, 'getUsageStats'])->name('raw-materials.usage-stats');
        
        // Recipe Management
        Route::resource('recipes', RecipeController::class);
        Route::get('recipes/{recipe}/calculate-cost', [RecipeController::class, 'calculateCost'])->name('recipes.calculate-cost');
        Route::post('recipes/{recipe}/duplicate', [RecipeController::class, 'duplicate'])->name('recipes.duplicate');
        
        // Production Planning
        Route::resource('production-plans', ProductionPlanController::class);
        Route::patch('production-plans/{productionPlan}/approve', [ProductionPlanController::class, 'approve'])->name('production-plans.approve');
        Route::patch('production-plans/{productionPlan}/start', [ProductionPlanController::class, 'start'])->name('production-plans.start');
        Route::patch('production-plans/{productionPlan}/complete', [ProductionPlanController::class, 'complete'])->name('production-plans.complete');
        Route::get('production-plans/{productionPlan}/material-requirements', [ProductionPlanController::class, 'materialRequirements'])->name('production-plans.material-requirements');
        Route::post('production-plans/{productionPlan}/record-usage', [ProductionPlanController::class, 'recordActualUsage'])->name('production-plans.record-usage');
        
        // Production Reports
        Route::get('production-reports', [ProductionReportController::class, 'index'])->name('production-reports.index');
        Route::get('production-reports/variance-analysis', [ProductionReportController::class, 'varianceAnalysis'])->name('production-reports.variance-analysis');
        Route::get('production-reports/material-efficiency', [ProductionReportController::class, 'materialEfficiency'])->name('production-reports.material-efficiency');
        Route::get('production-reports/production-summary', [ProductionReportController::class, 'productionSummary'])->name('production-reports.production-summary');
        Route::get('production-reports/cost-analysis', [ProductionReportController::class, 'costAnalysis'])->name('production-reports.cost-analysis');
        Route::get('production-reports/variance-analysis/export', [ProductionReportController::class, 'exportVarianceAnalysis'])->name('production-reports.variance-analysis.export');
        Route::get('production-reports/material-efficiency/export', [ProductionReportController::class, 'exportMaterialEfficiency'])->name('production-reports.material-efficiency.export');
        
        Route::resource('orders', OrderController::class);
        Route::post('orders/{order}/convert-to-sale', [OrderController::class, 'convertToSale'])->name('orders.convert-to-sale');
        Route::get('orders/{order}/create-purchase', [OrderController::class, 'createPurchaseForm'])->name('orders.create-purchase-form');
        Route::post('orders/{order}/create-purchase', [OrderController::class, 'createPurchase'])->name('orders.create-purchase');
        
        // Invoices
        Route::resource('invoices', InvoiceController::class);
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'generatePdf'])->name('invoices.pdf');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::post('invoices/{invoice}/send-to-printer', [InvoiceController::class, 'sendToPrinter'])->name('invoices.send-to-printer');
        Route::get('invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
        Route::post('invoices/create-from-sale/{sale}', [InvoiceController::class, 'createFromSale'])->name('invoices.create-from-sale');
        Route::post('invoices/create-from-order/{order}', [InvoiceController::class, 'createFromOrder'])->name('invoices.create-from-order');
        Route::patch('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
        Route::patch('invoices/{invoice}/mark-sent', [InvoiceController::class, 'markAsSent'])->name('invoices.mark-sent');
        Route::get('api/printers', [InvoiceController::class, 'getPrinters'])->name('api.printers');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('suppliers', SupplierController::class);

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports/sales', [ReportController::class, 'salesReport'])->name('reports.sales');
        
        // Staff Management
        Route::resource('staff', StaffController::class);
        Route::get('staff-create-simple', function() {
            $supervisors = \App\Models\Staff::where('status', 'active')->get();
            $users = \App\Models\User::whereDoesntHave('staff')->get();
            return view('staff.create-simple', compact('supervisors', 'users'));
        })->name('staff.create.simple');
        Route::get('staff/{staff}/charges', [StaffController::class, 'charges'])->name('staff.charges');
        Route::get('staff/{staff}/charges/create', [StaffController::class, 'createCharge'])->name('staff.charges.create');
        Route::post('staff/{staff}/charges', [StaffController::class, 'storeCharge'])->name('staff.charges.store');
        Route::get('staff-dashboard', [StaffController::class, 'dashboard'])->name('staff.dashboard');
        
        // Staff Daily Charges
        Route::resource('staff-charges', StaffDailyChargeController::class);
        Route::patch('staff-charges/{staffCharge}/approve', [StaffDailyChargeController::class, 'approve'])->name('staff-charges.approve');
        Route::patch('staff-charges/{staffCharge}/mark-paid', [StaffDailyChargeController::class, 'markAsPaid'])->name('staff-charges.mark-paid');
        
        // Profit & Loss Statements
        Route::resource('profit-loss', ProfitLossController::class);
        Route::get('profit-loss-quick', [ProfitLossController::class, 'quickReport'])->name('profit-loss.quick');
        Route::patch('profit-loss/{profitLoss}/finalize', [ProfitLossController::class, 'finalize'])->name('profit-loss.finalize');
    });
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Test route for production features
Route::get('/test-production', function () {
    $recipes = \App\Models\Recipe::with(['product', 'recipeItems.rawMaterial'])->get();
    $plans = \App\Models\ProductionPlan::with('productionPlanItems.product')->get();
    
    return response()->json([
        'message' => 'Production features are working!',
        'recipes_count' => $recipes->count(),
        'production_plans_count' => $plans->count(),
        'sample_recipe' => $recipes->first()?->toArray(),
    ]);
});

// Test route for staff features
Route::get('/test-staff', function () {
    $staff = \App\Models\Staff::with(['user', 'supervisor', 'dailyCharges'])->get();
    $charges = \App\Models\StaffDailyCharge::with(['user', 'staff'])->get();
    
    return response()->json([
        'message' => 'Staff management features are working!',
        'staff_count' => $staff->count(),
        'charges_count' => $charges->count(),
        'sample_staff' => $staff->first()?->toArray(),
        'departments' => $staff->pluck('department')->unique()->values(),
    ]);
});

// Test route for staff creation
Route::get('/test-staff-create', function () {
    try {
        $staff = \App\Models\Staff::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test.user.' . time() . '@example.com',
            'hire_date' => now(),
            'position' => 'Test Position',
            'base_salary' => 3000,
            'hourly_rate' => 15,
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Staff created successfully!',
            'staff' => $staff->toArray(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ]);
    }
});

// Debug route for staff form submission
Route::post('/debug-staff-create', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'request_data' => $request->all(),
        'validation_errors' => [],
        'files' => $request->hasFile('profile_photo') ? 'Has file' : 'No file',
    ]);
})->name('debug.staff.store');
