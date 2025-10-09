#!/bin/bash

echo "🌱 Seeding Sample Data"
echo "====================="

echo "👤 Creating admin user..."
docker exec laravel-inventory php artisan tinker --execute="
    DB::table('users')->insert([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
        'role' => 'admin',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo 'Admin user created: admin@example.com / password';
"

echo ""
echo "🏷️  Creating sample categories..."
docker exec laravel-inventory php artisan tinker --execute="
    \$categories = [
        ['name' => 'Electronics'],
        ['name' => 'Clothing'],
        ['name' => 'Books'],
        ['name' => 'Home & Garden']
    ];
    
    foreach(\$categories as \$category) {
        \$category['created_at'] = now();
        \$category['updated_at'] = now();
        DB::table('categories')->insert(\$category);
    }
    echo 'Sample categories created';
"

echo ""
echo "📦 Creating sample products..."
docker exec laravel-inventory php artisan tinker --execute="
    \$products = [
        ['name' => 'Laptop', 'price' => 999.99, 'quantity' => 10, 'category_id' => 1, 'unit' => 'pcs'],
        ['name' => 'T-Shirt', 'price' => 19.99, 'quantity' => 50, 'category_id' => 2, 'unit' => 'pcs'],
        ['name' => 'Programming Book', 'price' => 39.99, 'quantity' => 25, 'category_id' => 3, 'unit' => 'pcs'],
        ['name' => 'Garden Hose', 'price' => 29.99, 'quantity' => 15, 'category_id' => 4, 'unit' => 'pcs']
    ];
    
    foreach(\$products as \$product) {
        \$product['created_at'] = now();
        \$product['updated_at'] = now();
        DB::table('products')->insert(\$product);
    }
    echo 'Sample products created';
"

echo ""
echo "🏢 Creating sample suppliers..."
docker exec laravel-inventory php artisan tinker --execute="
    \$suppliers = [
        ['name' => 'Tech Supplies Inc', 'email' => 'contact@techsupplies.com', 'phone' => '123-456-7890'],
        ['name' => 'Fashion Wholesale', 'email' => 'orders@fashionwholesale.com', 'phone' => '098-765-4321']
    ];
    
    foreach(\$suppliers as \$supplier) {
        \$supplier['created_at'] = now();
        \$supplier['updated_at'] = now();
        DB::table('suppliers')->insert(\$supplier);
    }
    echo 'Sample suppliers created';
"

echo ""
echo "👥 Creating sample customers..."
docker exec laravel-inventory php artisan tinker --execute="
    \$customers = [
        ['name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '555-0101'],
        ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'phone' => '555-0102']
    ];
    
    foreach(\$customers as \$customer) {
        \$customer['created_at'] = now();
        \$customer['updated_at'] = now();
        DB::table('customers')->insert(\$customer);
    }
    echo 'Sample customers created';
"

echo ""
echo "✅ Sample data seeded successfully!"
echo ""
echo "🔑 Login credentials:"
echo "   Email: admin@example.com"
echo "   Password: password"
echo ""
echo "📊 View your data:"
echo "   ./view-database.sh count"
echo "   ./view-database.sh users"
echo "   ./view-database.sh products"