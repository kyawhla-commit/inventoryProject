<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\RawMaterialController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\ProductionPlanController;
use App\Http\Controllers\Api\ProductionPlanItemController;
use App\Http\Controllers\Api\InventoryTransactionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\BarcodeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\ExportController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    
    // Product routes
    Route::apiResource('products', ProductController::class);
    Route::get('/products/low-stock', [ProductController::class, 'lowStock']);
    
    // Category routes
    Route::apiResource('categories', CategoryController::class);
    
    // Raw Material routes
    Route::apiResource('raw-materials', RawMaterialController::class);
    Route::get('/raw-materials/low-stock', [RawMaterialController::class, 'lowStock']);
    Route::patch('/raw-materials/{id}/stock', [RawMaterialController::class, 'updateStock']);
    
    // Supplier routes
    Route::apiResource('suppliers', SupplierController::class);
    
    // Customer routes
    Route::apiResource('customers', CustomerController::class);
    Route::get('/customers/{id}/orders', [CustomerController::class, 'orders']);
    Route::get('/customers/{id}/sales', [CustomerController::class, 'sales']);
    
    // Order routes
    Route::apiResource('orders', OrderController::class);
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{id}/convert-to-sale', [OrderController::class, 'convertToSale']);
    Route::get('/orders/status/{status}', [OrderController::class, 'getByStatus']);
    
    // Sale routes
    Route::apiResource('sales', SaleController::class);
    Route::get('/sales/today', [SaleController::class, 'todaySales']);
    
    // Purchase routes
    Route::apiResource('purchases', PurchaseController::class);
    Route::patch('/purchases/{id}/status', [PurchaseController::class, 'updateStatus']);
    Route::post('/purchases/{id}/receive', [PurchaseController::class, 'receive']);
    Route::get('/purchases/status/{status}', [PurchaseController::class, 'getByStatus']);
    
    // Recipe routes
    Route::apiResource('recipes', RecipeController::class);
    Route::post('/recipes/{id}/calculate-materials', [RecipeController::class, 'calculateMaterials']);
    Route::post('/recipes/{id}/duplicate', [RecipeController::class, 'duplicate']);
    
    // Production Plan routes
    Route::apiResource('production-plans', ProductionPlanController::class);
    Route::patch('/production-plans/{id}/status', [ProductionPlanController::class, 'updateStatus']);
    Route::get('/production-plans/{id}/material-requirements', [ProductionPlanController::class, 'getMaterialRequirements']);
    Route::get('/production-plans/status/{status}', [ProductionPlanController::class, 'getByStatus']);
    
    // Production Plan Item routes
    Route::get('/production-plan-items/{id}', [ProductionPlanItemController::class, 'show']);
    Route::patch('/production-plan-items/{id}/status', [ProductionPlanItemController::class, 'updateStatus']);
    Route::patch('/production-plan-items/{id}/progress', [ProductionPlanItemController::class, 'updateProgress']);
    Route::get('/production-plan-items/{id}/material-requirements', [ProductionPlanItemController::class, 'getMaterialRequirements']);
    Route::get('/production-plan-items/by-priority', [ProductionPlanItemController::class, 'getByPriority']);
    Route::get('/production-plan-items/overdue', [ProductionPlanItemController::class, 'getOverdue']);
    
    // Inventory Transaction routes
    Route::get('/inventory-transactions', [InventoryTransactionController::class, 'index']);
    Route::post('/inventory-transactions', [InventoryTransactionController::class, 'store']);
    Route::get('/inventory-transactions/{id}', [InventoryTransactionController::class, 'show']);
    Route::get('/inventory-transactions/item/history', [InventoryTransactionController::class, 'getItemHistory']);
    Route::post('/inventory-transactions/adjustments', [InventoryTransactionController::class, 'createAdjustment']);
    Route::get('/inventory-transactions/reports/movements', [InventoryTransactionController::class, 'getStockMovements']);
    Route::get('/inventory-transactions/reports/low-stock', [InventoryTransactionController::class, 'getLowStockItems']);
    
    // Reports routes
    Route::get('/reports/sales', [ReportController::class, 'salesReport']);
    Route::get('/reports/inventory-valuation', [ReportController::class, 'inventoryValuationReport']);
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLossReport']);
    Route::get('/reports/production-efficiency', [ReportController::class, 'productionEfficiencyReport']);
    Route::get('/reports/low-stock-alert', [ReportController::class, 'lowStockAlert']);
    
    // Analytics routes
    Route::get('/analytics/sales-trends', [AnalyticsController::class, 'salesTrends']);
    Route::get('/analytics/product-performance', [AnalyticsController::class, 'productPerformance']);
    Route::get('/analytics/customer-analytics', [AnalyticsController::class, 'customerAnalytics']);
    Route::get('/analytics/inventory-turnover', [AnalyticsController::class, 'inventoryTurnover']);
    Route::get('/analytics/forecast-demand', [AnalyticsController::class, 'forecastDemand']);
    
    // Barcode/QR Code routes
    Route::post('/barcodes/generate', [BarcodeController::class, 'generateBarcode']);
    Route::post('/barcodes/generate-bulk', [BarcodeController::class, 'generateBulkBarcodes']);
    Route::post('/barcodes/scan', [BarcodeController::class, 'scanBarcode']);
    Route::get('/barcodes/svg', [BarcodeController::class, 'generateSVG'])->name('api.barcode.svg');
    Route::get('/barcodes/png', [BarcodeController::class, 'generatePNG'])->name('api.barcode.png');
    
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::get('/notifications/{id}', [NotificationController::class, 'show']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/{id}/dismiss', [NotificationController::class, 'dismiss']);
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread/count', [NotificationController::class, 'getUnreadCount']);
    Route::post('/notifications/check-low-stock', [NotificationController::class, 'checkLowStock']);
    Route::post('/notifications/order-update', [NotificationController::class, 'createOrderNotification']);
    Route::get('/notifications/types', [NotificationController::class, 'getNotificationTypes']);
    Route::get('/notifications/stats', [NotificationController::class, 'getStats']);
    
    // File Upload routes
    Route::post('/files/upload/product-image', [FileUploadController::class, 'uploadProductImage']);
    Route::post('/files/upload/document', [FileUploadController::class, 'uploadDocument']);
    Route::post('/files/upload/bulk-images', [FileUploadController::class, 'uploadBulkImages']);
    Route::delete('/files/delete', [FileUploadController::class, 'deleteFile']);
    Route::get('/files/list', [FileUploadController::class, 'getFiles']);
    Route::get('/files/storage-info', [FileUploadController::class, 'getStorageInfo']);
    
    // Export/Backup routes
    Route::post('/export/products', [ExportController::class, 'exportProducts']);
    Route::post('/export/inventory-transactions', [ExportController::class, 'exportInventoryTransactions']);
    Route::post('/export/sales-report', [ExportController::class, 'exportSalesReport']);
    Route::post('/backup/create', [ExportController::class, 'createBackup']);
    Route::get('/export/history', [ExportController::class, 'getExportHistory']);
    Route::delete('/export/delete', [ExportController::class, 'deleteExport']);

    
    
    // Dashboard routes
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    Route::get('/dashboard/sales-stats', [DashboardController::class, 'salesStats']);
    Route::get('/dashboard/inventory-value', [DashboardController::class, 'inventoryValue']);

    // routes/api.php
Route::get('/dashboard/alerts/detail', [DashboardController::class, 'inventoryAlertsDetail']);
Route::get('/dashboard/sales/trend', [DashboardController::class, 'salesTrend']);
Route::get('/dashboard/purchases/trend', [DashboardController::class, 'purchasesTrend']);
Route::get('/dashboard/categories/breakdown', [DashboardController::class, 'categoryBreakdown']);
Route::get('/dashboard/suppliers/stats', [DashboardController::class, 'supplierStats']);
Route::get('/dashboard/profit/estimate', [DashboardController::class, 'profitEstimate']);
    
Route::get('/dashboard/monthly-history', [DashboardController::class, 'monthlyHistory']);
Route::get('/dashboard/top-sellers', [DashboardController::class, 'topSellers']);

Route::get('/dashboard/orders/status-summary', [DashboardController::class, 'orderStatusSummary']);

    // User route
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
