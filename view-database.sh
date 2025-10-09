#!/bin/bash

echo "📊 Laravel Database Viewer"
echo "========================="

if ! docker ps --filter name=laravel-inventory --format "{{.Names}}" | grep -q laravel-inventory; then
    echo "❌ Container 'laravel-inventory' is not running"
    echo "Run: ./docker-manage.sh start"
    exit 1
fi

echo "🗄️  Database Location: /var/www/html/database/database.sqlite"
echo ""

case "$1" in
    "tables")
        echo "📋 Database Tables:"
        docker exec laravel-inventory php artisan tinker --execute="
            \$tables = DB::select('SELECT name FROM sqlite_master WHERE type=\"table\" ORDER BY name');
            foreach(\$tables as \$table) {
                echo \$table->name . PHP_EOL;
            }
        "
        ;;
    "users")
        echo "👥 Users Table:"
        docker exec laravel-inventory php artisan tinker --execute="
            \$users = DB::table('users')->select('id', 'name', 'email', 'role', 'created_at')->get();
            foreach(\$users as \$user) {
                echo 'ID: ' . \$user->id . ' | Name: ' . \$user->name . ' | Email: ' . \$user->email . ' | Role: ' . (\$user->role ?? 'user') . ' | Created: ' . \$user->created_at . PHP_EOL;
            }
        "
        ;;
    "products")
        echo "📦 Products Table:"
        docker exec laravel-inventory php artisan tinker --execute="
            \$products = DB::table('products')->select('id', 'name', 'price', 'quantity', 'category_id')->limit(10)->get();
            foreach(\$products as \$product) {
                echo 'ID: ' . \$product->id . ' | Name: ' . \$product->name . ' | Price: ' . \$product->price . ' | Qty: ' . \$product->quantity . ' | Category: ' . \$product->category_id . PHP_EOL;
            }
        "
        ;;
    "categories")
        echo "🏷️  Categories Table:"
        docker exec laravel-inventory php artisan tinker --execute="
            \$categories = DB::table('categories')->select('id', 'name', 'description')->get();
            foreach(\$categories as \$category) {
                echo 'ID: ' . \$category->id . ' | Name: ' . \$category->name . ' | Description: ' . (\$category->description ?? 'N/A') . PHP_EOL;
            }
        "
        ;;
    "sales")
        echo "💰 Recent Sales:"
        docker exec laravel-inventory php artisan tinker --execute="
            \$sales = DB::table('sales')->select('id', 'total_amount', 'customer_id', 'created_at')->orderBy('created_at', 'desc')->limit(10)->get();
            foreach(\$sales as \$sale) {
                echo 'ID: ' . \$sale->id . ' | Amount: ' . \$sale->total_amount . ' | Customer: ' . \$sale->customer_id . ' | Date: ' . \$sale->created_at . PHP_EOL;
            }
        "
        ;;
    "count")
        echo "📊 Record Counts:"
        docker exec laravel-inventory php artisan tinker --execute="
            \$tables = ['users', 'products', 'categories', 'sales', 'purchases', 'customers', 'suppliers'];
            foreach(\$tables as \$table) {
                try {
                    \$count = DB::table(\$table)->count();
                    echo ucfirst(\$table) . ': ' . \$count . PHP_EOL;
                } catch (Exception \$e) {
                    echo ucfirst(\$table) . ': Table not found' . PHP_EOL;
                }
            }
        "
        ;;
    "shell")
        echo "🐚 Opening SQLite shell..."
        echo "Commands you can use:"
        echo "  .tables          - List all tables"
        echo "  .schema users    - Show table structure"
        echo "  SELECT * FROM users LIMIT 5;"
        echo "  .quit            - Exit"
        echo ""
        docker exec -it laravel-inventory sqlite3 /var/www/html/database/database.sqlite
        ;;
    "backup")
        echo "💾 Creating database backup..."
        docker cp laravel-inventory:/var/www/html/database/database.sqlite ./database_backup_$(date +%Y%m%d_%H%M%S).sqlite
        echo "✅ Backup created: database_backup_$(date +%Y%m%d_%H%M%S).sqlite"
        ;;
    *)
        echo "Usage: $0 {tables|users|products|categories|sales|count|shell|backup}"
        echo ""
        echo "Commands:"
        echo "  tables     - List all database tables"
        echo "  users      - Show users data"
        echo "  products   - Show products data"
        echo "  categories - Show categories data"
        echo "  sales      - Show recent sales"
        echo "  count      - Show record counts for all tables"
        echo "  shell      - Open interactive SQLite shell"
        echo "  backup     - Create database backup"
        echo ""
        echo "Examples:"
        echo "  $0 tables"
        echo "  $0 users"
        echo "  $0 shell"
        ;;
esac