#!/bin/bash

echo "🌱 Running Laravel Database Seeders"
echo "==================================="

if ! docker ps --filter name=laravel-inventory --format "{{.Names}}" | grep -q laravel-inventory; then
    echo "❌ Container 'laravel-inventory' is not running"
    echo "Run: ./docker-manage.sh start"
    exit 1
fi

echo "🗄️  Running database seeders..."
docker exec laravel-inventory php artisan db:seed

echo ""
echo "📊 Database populated! Here's what was created:"
echo ""

echo "👥 Users:"
docker exec laravel-inventory php artisan tinker --execute="
    \$users = DB::table('users')->select('id', 'name', 'email', 'role')->get();
    foreach(\$users as \$user) {
        echo 'ID: ' . \$user->id . ' | Name: ' . \$user->name . ' | Email: ' . \$user->email . ' | Role: ' . (\$user->role ?? 'user') . PHP_EOL;
    }
"

echo ""
echo "🏷️  Categories:"
docker exec laravel-inventory php artisan tinker --execute="
    \$categories = DB::table('categories')->select('id', 'name')->get();
    foreach(\$categories as \$category) {
        echo 'ID: ' . \$category->id . ' | Name: ' . \$category->name . PHP_EOL;
    }
"

echo ""
echo "📦 Products (first 5):"
docker exec laravel-inventory php artisan tinker --execute="
    \$products = DB::table('products')->select('id', 'name', 'price', 'quantity')->limit(5)->get();
    foreach(\$products as \$product) {
        echo 'ID: ' . \$product->id . ' | Name: ' . \$product->name . ' | Price: $' . \$product->price . ' | Qty: ' . \$product->quantity . PHP_EOL;
    }
"

echo ""
echo "📊 Record Counts:"
docker exec laravel-inventory php artisan tinker --execute="
    \$tables = ['users', 'categories', 'products', 'customers', 'suppliers', 'sales', 'purchases', 'orders'];
    foreach(\$tables as \$table) {
        try {
            \$count = DB::table(\$table)->count();
            echo ucfirst(\$table) . ': ' . \$count . PHP_EOL;
        } catch (Exception \$e) {
            echo ucfirst(\$table) . ': Error' . PHP_EOL;
        }
    }
"

echo ""
echo "✅ Database seeding completed!"
echo ""
echo "🔍 To explore your data further:"
echo "   ./view-database.sh tables    - List all tables"
echo "   ./view-database.sh users     - View users"
echo "   ./view-database.sh products  - View products"
echo "   ./view-database.sh count     - View all record counts"
echo "   ./docker-manage.sh db        - Open SQLite shell"