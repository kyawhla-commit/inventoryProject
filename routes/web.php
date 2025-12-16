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
    use App\Http\Controllers\ProductionDashboardController;
    use App\Http\Controllers\ProductionMaterialUsageController;
    use App\Http\Controllers\StaffController;
    use App\Http\Controllers\ProductRawMaterialController;
    use App\Http\Controllers\StockManagementController;



    use App\Http\Controllers\ProductionCostController;
    use App\Http\Controllers\DeliveryController;




    use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

    Auth::routes();

    Route::middleware('auth')->group(function () {
        // Temporary test route - remove after testing
        Route::get('raw-material-usages/bulk-create-test', [RawMaterialUsageController::class, 'bulkCreate'])
            ->name('raw-material-usages.bulk-create-test');
            
        // ... rest of your routes
        Route::get('raw-materials/{rawMaterial}/purchase-history', [RawMaterialController::class, 'purchaseHistory'])
            ->name('raw-materials.purchase-history');
    });

    // Language switcher - must be in web middleware group for session support
    Route::middleware('web')->get('lang/{locale}', function ($locale) {
        if (! in_array($locale, ['en', 'mm'])) {
            abort(400);
        }
        
        session()->put('locale', $locale);
        app()->setLocale($locale);
        
        // Get the previous URL or fallback to home
        $previousUrl = url()->previous();
        $appUrl = config('app.url');
        
        // Ensure we redirect to a valid URL within the app
        if (empty($previousUrl) || $previousUrl === url()->current()) {
            $previousUrl = '/';
        }
        
        return redirect($previousUrl)
            ->withCookie(cookie()->forever('locale', $locale));
    })->name('lang.switch');


    Route::get('raw-material-usages/bulk-create-test', [RawMaterialUsageController::class, 'bulkCreate'])
    ->name('raw-material-usages.bulk-create-test');

    Route::middleware('auth')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/dashboard/goal', [DashboardController::class, 'updateGoal'])->name('dashboard.goal');

        Route::middleware('role:admin,staff')->group(function () {
            // Product Management
            Route::resource('products', ProductController::class);
            Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');
            Route::get('products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
            
            // Product Raw Material Relationships
            Route::get('products/{product}/raw-materials', [ProductRawMaterialController::class, 'index'])->name('products.raw-materials.index');
            Route::get('products/{product}/raw-materials/create', [ProductRawMaterialController::class, 'create'])->name('products.raw-materials.create');
            Route::post('products/{product}/raw-materials', [ProductRawMaterialController::class, 'store'])->name('products.raw-materials.store');
            Route::get('products/{product}/raw-materials/{rawMaterial}/edit', [ProductRawMaterialController::class, 'edit'])->name('products.raw-materials.edit');
            Route::put('products/{product}/raw-materials/{rawMaterial}', [ProductRawMaterialController::class, 'update'])->name('products.raw-materials.update');
            Route::delete('products/{product}/raw-materials/{rawMaterial}', [ProductRawMaterialController::class, 'destroy'])->name('products.raw-materials.destroy');
            Route::get('products/{product}/calculate-cost', [ProductRawMaterialController::class, 'calculateCost'])->name('products.calculate-cost');
            Route::post('products/{product}/raw-materials/bulk-add', [ProductRawMaterialController::class, 'bulkAdd'])->name('products.raw-materials.bulk-add');
            
            // Purchase & Sales
            Route::resource('purchases', PurchaseController::class);
            Route::post('purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve');
            Route::post('purchases/{purchase}/confirm', [PurchaseController::class, 'confirm'])->name('purchases.confirm');
            Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
            Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
            Route::get('purchases/{purchase}/duplicate', [PurchaseController::class, 'duplicate'])->name('purchases.duplicate');
            Route::get('purchases/{purchase}/print', [PurchaseController::class, 'print'])->name('purchases.print');
            Route::get('purchases-export', [PurchaseController::class, 'export'])->name('purchases.export');
            Route::get('api/suppliers/{supplier}/materials', [PurchaseController::class, 'getMaterialsBySupplier'])->name('api.supplier.materials');
            Route::get('api/purchases/suggested-order', [PurchaseController::class, 'getSuggestedOrder'])->name('api.purchases.suggested');
            Route::resource('sales', SaleController::class);
            Route::resource('customers', CustomerController::class);
            
            // Raw Materials
            Route::resource('raw-materials', RawMaterialController::class);
            Route::get('raw-materials-low-stock', [RawMaterialController::class, 'lowStock'])->name('raw-materials.low-stock');
            Route::post('raw-materials/{rawMaterial}/adjust-stock', [RawMaterialController::class, 'adjustStock'])->name('raw-materials.adjust-stock');
            Route::get('raw-materials-export', [RawMaterialController::class, 'export'])->name('raw-materials.export');
            Route::get('api/raw-materials/{rawMaterial}/usage-stats', [RawMaterialController::class, 'usageStats'])->name('api.raw-materials.usage-stats');
            
            // Raw Material Usage Tracking
            Route::resource('raw-material-usages', RawMaterialUsageController::class);
            Route::get('raw-material-usages/bulk-create', [RawMaterialUsageController::class, 'bulkCreate'])->name('raw-material-usages.bulk-create');
            Route::post('raw-material-usages/bulk-store', [RawMaterialUsageController::class, 'bulkStore'])->name('raw-material-usages.bulk-store');
            Route::get('raw-material-usages/efficiency', [RawMaterialUsageController::class, 'efficiency'])->name('raw-material-usages.efficiency');
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
            
            // Production Dashboard
            Route::get('production-dashboard', [ProductionDashboardController::class, 'index'])->name('production-plans.dashboard');
            
            // Stock Management
            Route::get('stock-management', [StockManagementController::class, 'index'])->name('stock-management.index');
            Route::get('stock-management/add-raw-material', [StockManagementController::class, 'addRawMaterialForm'])->name('stock-management.add-raw-material');
            Route::post('stock-management/add-raw-material', [StockManagementController::class, 'addRawMaterialStock'])->name('stock-management.add-raw-material.store');
            Route::get('stock-management/add-product', [StockManagementController::class, 'addProductForm'])->name('stock-management.add-product');
            Route::post('stock-management/add-product', [StockManagementController::class, 'addProductStock'])->name('stock-management.add-product.store');
            Route::get('stock-management/deduct', [StockManagementController::class, 'deductForm'])->name('stock-management.deduct');
            Route::post('stock-management/deduct-raw-material', [StockManagementController::class, 'deductRawMaterialStock'])->name('stock-management.deduct-raw-material');
            Route::post('stock-management/deduct-product', [StockManagementController::class, 'deductProductStock'])->name('stock-management.deduct-product');
            Route::get('stock-management/movements', [StockManagementController::class, 'movements'])->name('stock-management.movements');
            Route::get('stock-management/valuation', [StockManagementController::class, 'valuation'])->name('stock-management.valuation');
            Route::get('stock-management/bulk-adjustment', [StockManagementController::class, 'bulkAdjustmentForm'])->name('stock-management.bulk-adjustment');
            Route::post('stock-management/bulk-adjustment', [StockManagementController::class, 'bulkAdjustment'])->name('stock-management.bulk-adjustment.store');
            Route::get('stock-management/view-all', [StockManagementController::class, 'viewAllStock'])->name('stock-management.view-all');
            Route::get('stock-management/export', [StockManagementController::class, 'exportStock'])->name('stock-management.export');
            
            // Production Material Usage
            Route::get('/production-material-usage', [ProductionMaterialUsageController::class, 'index'])
                ->name('production-material-usage.index');
            Route::get('/production-material-usage/efficiency', [ProductionMaterialUsageController::class, 'efficiency'])
                ->name('production-material-usage.efficiency');
            Route::get('/production-material-usage/stock-impact', [ProductionMaterialUsageController::class, 'stockImpact'])
                ->name('production-material-usage.stock-impact');
            Route::get('/production-material-usage/waste-analysis', [ProductionMaterialUsageController::class, 'wasteAnalysis'])
                ->name('production-material-usage.waste-analysis');
            Route::get('/production-plans/{productionPlan}/record-material-usage', [ProductionMaterialUsageController::class, 'recordForProduction'])
                ->name('production-material-usage.record');
            Route::post('/production-plans/{productionPlan}/record-material-usage', [ProductionMaterialUsageController::class, 'storeForProduction'])
                ->name('production-material-usage.store');
            Route::get('/production-plans/{productionPlan}/requirements-comparison', [ProductionMaterialUsageController::class, 'requirementsComparison'])
                ->name('production-material-usage.requirements-comparison');
            
            // Production Reports
            Route::get('production-reports', [ProductionReportController::class, 'index'])->name('production-reports.index');
            Route::get('production-reports/variance-analysis', [ProductionReportController::class, 'varianceAnalysis'])->name('production-reports.variance-analysis');
            Route::get('production-reports/material-efficiency', [ProductionReportController::class, 'materialEfficiency'])->name('production-reports.material-efficiency');
            Route::get('production-reports/production-summary', [ProductionReportController::class, 'productionSummary'])->name('production-reports.production-summary');
            Route::get('production-reports/cost-analysis', [ProductionReportController::class, 'costAnalysis'])->name('production-reports.cost-analysis');
            Route::get('production-reports/variance-analysis/export', [ProductionReportController::class, 'exportVarianceAnalysis'])->name('production-reports.variance-analysis.export');
            Route::get('production-reports/material-efficiency/export', [ProductionReportController::class, 'exportMaterialEfficiency'])->name('production-reports.material-efficiency.export');
            
            // Orders & Invoices
            Route::resource('orders', OrderController::class);
            Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
            Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
            Route::post('orders/{order}/convert-to-sale', [OrderController::class, 'convertToSale'])->name('orders.convert-to-sale');
            Route::post('orders/{order}/create-invoice', [OrderController::class, 'createInvoice'])->name('orders.create-invoice');
            Route::get('orders/{order}/create-purchase', [OrderController::class, 'createPurchaseForm'])->name('orders.create-purchase-form');
            Route::post('orders/{order}/create-purchase', [OrderController::class, 'createPurchase'])->name('orders.create-purchase');
            
            // Deliveries
            Route::get('deliveries/dashboard', [DeliveryController::class, 'dashboard'])->name('deliveries.dashboard');
            Route::resource('deliveries', DeliveryController::class);
            Route::post('deliveries/{delivery}/status', [DeliveryController::class, 'updateStatus'])->name('deliveries.update-status');
            Route::post('deliveries/{delivery}/assign-driver', [DeliveryController::class, 'assignDriver'])->name('deliveries.assign-driver');
            Route::post('deliveries/{delivery}/cancel', [DeliveryController::class, 'cancel'])->name('deliveries.cancel');

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
            // Categories & Suppliers
            Route::resource('categories', CategoryController::class);
            Route::resource('suppliers', SupplierController::class);

            // Reports
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

    Route::middleware(['auth'])->group(function () {
        // Production Cost Routes
        Route::get('/production-costs/dashboard', [ProductionCostController::class, 'dashboard'])
            ->name('production-costs.dashboard');
            Route::get('/production-cost', 
            [ProductionCostController::class, 'index']);
        Route::get('/production-costs/{productionPlan}', [ProductionCostController::class, 'show'])
            ->name('production-costs.show');
        Route::post('/production-costs/{productionPlan}/update-actual', [ProductionCostController::class, 'updateActualCosts'])
            ->name('production-costs.update-actual');
    });


    Route::middleware(['auth'])->group(function () {
        // Production Cost Routes
        Route::get('/production-costs/dashboard', [ProductionCostController::class, 'dashboard'])
            ->name('production-costs.dashboard');
            Route::get('/production-cost', [ProductionCostController::class, 'index']);
        Route::get('/production-costs/{productionPlan}', [ProductionCostController::class, 'show'])
            ->name('production-costs.show');
        Route::post('/production-costs/{productionPlan}/update-actual', [ProductionCostController::class, 'updateActualCosts'])
            ->name('production-costs.update-actual');
    });


Route::get('/test', function() {
    try {
        DB::connection()->getPdo();
        return "Database connected successfully!";
    } catch (\Exception $e) {
        return "Database connection failed: " . $e->getMessage();
    }
});
