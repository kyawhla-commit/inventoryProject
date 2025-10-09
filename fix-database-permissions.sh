#!/bin/bash

echo "🔧 Fixing Database Permissions"
echo "=============================="

echo "📊 Current database permissions:"
docker exec laravel-inventory ls -la /var/www/html/database/

echo ""
echo "🔧 Fixing permissions..."
docker exec laravel-inventory chown -R www-data:www-data /var/www/html/database
docker exec laravel-inventory chmod -R 775 /var/www/html/database
docker exec laravel-inventory chmod 664 /var/www/html/database/database.sqlite

echo ""
echo "📊 Updated permissions:"
docker exec laravel-inventory ls -la /var/www/html/database/

echo ""
echo "🧪 Testing database write access..."
if docker exec laravel-inventory php artisan tinker --execute="DB::table('cache')->insert(['key' => 'permission_test_' . time(), 'value' => 'test', 'expiration' => time() + 3600]); echo 'SUCCESS';" > /dev/null 2>&1; then
    echo "✅ Database is writable"
else
    echo "❌ Database write test failed"
fi

echo ""
echo "🧹 Clearing caches..."
docker exec laravel-inventory php artisan cache:clear
docker exec laravel-inventory php artisan config:clear

echo ""
echo "✅ Database permissions fixed!"
echo "💡 The 'readonly database' error should now be resolved."