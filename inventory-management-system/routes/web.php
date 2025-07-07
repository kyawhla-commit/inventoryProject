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

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:admin,staff')->group(function () {
        Route::resource('products', ProductController::class);
        Route::resource('purchases', PurchaseController::class);
        Route::resource('sales', SaleController::class);
        Route::resource('customers', CustomerController::class);
        Route::resource('raw-materials', RawMaterialController::class);
        Route::get('raw-materials-low-stock', [RawMaterialController::class, 'lowStock'])->name('raw-materials.low-stock');
        Route::resource('orders', OrderController::class);
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('suppliers', SupplierController::class);

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports/sales', [ReportController::class, 'salesReport'])->name('reports.sales');
    });
});

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
