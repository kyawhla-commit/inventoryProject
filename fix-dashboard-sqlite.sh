#!/bin/bash

echo "🔧 Dashboard SQLite Compatibility Fix"
echo "===================================="

if ! docker ps --filter name=laravel-inventory --format "{{.Names}}" | grep -q laravel-inventory; then
    echo "❌ Container 'laravel-inventory' is not running"
    echo "Run: ./docker-manage.sh start"
    exit 1
fi

echo "🧪 Testing dashboard controller directly..."
RESULT=$(docker exec laravel-inventory php artisan tinker --execute="
try {
    \$request = new Illuminate\Http\Request();
    \$controller = new App\Http\Controllers\DashboardController();
    \$response = \$controller->index(\$request);
    echo 'SUCCESS';
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" 2>&1)

if [[ "$RESULT" == *"SUCCESS"* ]]; then
    echo "✅ Dashboard controller is working correctly"
    echo "🌐 You can now access the dashboard at http://localhost:8080"
    exit 0
fi

if [[ "$RESULT" == *"DATE_FORMAT"* ]]; then
    echo "❌ Found DATE_FORMAT SQLite compatibility issue"
    echo "🔧 Applying comprehensive fix..."
    
    # Clear all caches first
    docker exec laravel-inventory php artisan config:clear
    docker exec laravel-inventory php artisan cache:clear
    docker exec laravel-inventory php artisan view:clear
    docker exec laravel-inventory php artisan route:clear
    
    # Test individual queries to isolate the issue
    echo "🧪 Testing individual SQLite queries..."
    
    # Test sales query
    SALES_TEST=$(docker exec laravel-inventory php artisan tinker --execute="
    use App\Models\Sale;
    use Carbon\Carbon;
    use Illuminate\Support\Facades\DB;
    
    try {
        \$startDate = Carbon::now()->subMonths(11)->startOfMonth();
        \$endDate = Carbon::now()->endOfMonth();
        
        if (DB::connection()->getDriverName() === 'sqlite') {
            \$result = Sale::selectRaw('strftime(\"%Y-%m\", sale_date) as ym, SUM(total_amount) as total')
                ->whereBetween('sale_date', [\$startDate, \$endDate])
                ->groupBy('ym')
                ->pluck('total', 'ym');
        } else {
            \$result = Sale::selectRaw('DATE_FORMAT(sale_date, \"%Y-%m\") as ym, SUM(total_amount) as total')
                ->whereBetween('sale_date', [\$startDate, \$endDate])
                ->groupBy('ym')
                ->pluck('total', 'ym');
        }
        echo 'SALES_OK';
    } catch (Exception \$e) {
        echo 'SALES_ERROR: ' . \$e->getMessage();
    }
    " 2>&1)
    
    if [[ "$SALES_TEST" == *"SALES_OK"* ]]; then
        echo "✅ Sales query working"
    else
        echo "❌ Sales query failed: $SALES_TEST"
    fi
    
    # Test purchases query
    PURCHASE_TEST=$(docker exec laravel-inventory php artisan tinker --execute="
    use App\Models\Purchase;
    use Carbon\Carbon;
    use Illuminate\Support\Facades\DB;
    
    try {
        \$startDate = Carbon::now()->subMonths(11)->startOfMonth();
        \$endDate = Carbon::now()->endOfMonth();
        
        if (DB::connection()->getDriverName() === 'sqlite') {
            \$result = Purchase::selectRaw('strftime(\"%Y-%m\", purchase_date) as ym, SUM(total_amount) as total')
                ->whereBetween('purchase_date', [\$startDate, \$endDate])
                ->groupBy('ym')
                ->pluck('total', 'ym');
        } else {
            \$result = Purchase::selectRaw('DATE_FORMAT(purchase_date, \"%Y-%m\") as ym, SUM(total_amount) as total')
                ->whereBetween('purchase_date', [\$startDate, \$endDate])
                ->groupBy('ym')
                ->pluck('total', 'ym');
        }
        echo 'PURCHASE_OK';
    } catch (Exception \$e) {
        echo 'PURCHASE_ERROR: ' . \$e->getMessage();
    }
    " 2>&1)
    
    if [[ "$PURCHASE_TEST" == *"PURCHASE_OK"* ]]; then
        echo "✅ Purchase query working"
    else
        echo "❌ Purchase query failed: $PURCHASE_TEST"
    fi
    
    # Final test
    echo "🧪 Final dashboard test..."
    FINAL_TEST=$(docker exec laravel-inventory php artisan tinker --execute="
    try {
        \$request = new Illuminate\Http\Request();
        \$controller = new App\Http\Controllers\DashboardController();
        \$response = \$controller->index(\$request);
        echo 'FINAL_SUCCESS';
    } catch (Exception \$e) {
        echo 'FINAL_ERROR: ' . \$e->getMessage();
    }
    " 2>&1)
    
    if [[ "$FINAL_TEST" == *"FINAL_SUCCESS"* ]]; then
        echo "✅ Dashboard controller fixed and working!"
        echo "🌐 You can now access the dashboard at http://localhost:8080"
    else
        echo "❌ Dashboard still has issues: $FINAL_TEST"
        echo ""
        echo "🔍 Manual troubleshooting needed:"
        echo "   1. Check the DashboardController.php file for any remaining DATE_FORMAT queries"
        echo "   2. Look for cached queries or duplicate code sections"
        echo "   3. Consider rebuilding the container: ./docker-manage.sh rebuild"
    fi
    
else
    echo "❌ Dashboard has other issues: $RESULT"
fi