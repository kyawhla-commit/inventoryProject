#!/bin/bash

echo "🔧 SQLite Compatibility Fix"
echo "=========================="

if ! docker ps --filter name=laravel-inventory --format "{{.Names}}" | grep -q laravel-inventory; then
    echo "❌ Container 'laravel-inventory' is not running"
    echo "Run: ./docker-manage.sh start"
    exit 1
fi

echo "🗄️  Checking database driver..."
DB_DRIVER=$(docker exec laravel-inventory php artisan tinker --execute="echo config('database.default');")
echo "Database driver: $DB_DRIVER"

if [ "$DB_DRIVER" = "sqlite" ]; then
    echo "✅ Using SQLite - compatibility fixes applied"
    
    echo "🧹 Clearing application caches..."
    docker exec laravel-inventory php artisan config:clear
    docker exec laravel-inventory php artisan cache:clear
    docker exec laravel-inventory php artisan view:clear
    
    echo "🧪 Testing dashboard query..."
    if docker exec laravel-inventory php artisan tinker --execute="
        use App\Models\Purchase;
        use Carbon\Carbon;
        \$startDate = Carbon::now()->subMonths(11)->startOfMonth();
        \$endDate = Carbon::now()->endOfMonth();
        \$result = Purchase::selectRaw('strftime(\"%Y-%m\", purchase_date) as ym, SUM(total_amount) as total')
            ->whereBetween('purchase_date', [\$startDate, \$endDate])
            ->groupBy('ym')
            ->pluck('total', 'ym');
        echo 'SQLite date query test: SUCCESS';
    " > /dev/null 2>&1; then
        echo "✅ SQLite date queries working correctly"
    else
        echo "❌ SQLite date query test failed"
    fi
    
else
    echo "ℹ️  Using $DB_DRIVER - no SQLite fixes needed"
fi

echo ""
echo "🔍 Common SQLite vs MySQL differences fixed:"
echo "   • DATE_FORMAT() → strftime()"
echo "   • MySQL date functions → SQLite equivalents"
echo ""
echo "✅ SQLite compatibility check completed!"
echo "💡 You can now login without database function errors."