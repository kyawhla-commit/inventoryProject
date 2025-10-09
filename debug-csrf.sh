#!/bin/bash

echo "🔍 CSRF Debug Information"
echo "========================="

echo "📋 Container Status:"
docker ps --filter name=laravel-inventory --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

echo ""
echo "⚙️  Environment Configuration:"
docker exec laravel-inventory grep -E "(APP_URL|SESSION_|APP_KEY)" /var/www/html/.env

echo ""
echo "🔑 Application Key:"
docker exec laravel-inventory php artisan tinker --execute="echo 'Key exists: ' . (config('app.key') ? 'Yes' : 'No');"

echo ""
echo "📊 Session Configuration:"
docker exec laravel-inventory php artisan config:show session.driver
docker exec laravel-inventory php artisan config:show session.cookie

echo ""
echo "🌐 Testing CSRF Token Generation:"
TOKEN=$(docker exec laravel-inventory curl -s http://localhost/login | grep -o 'name="_token"[^>]*value="[^"]*"' | grep -o 'value="[^"]*"' | cut -d'"' -f2)
if [ -n "$TOKEN" ]; then
    echo "✅ CSRF Token generated: ${TOKEN:0:20}..."
else
    echo "❌ No CSRF token found"
fi

echo ""
echo "📁 Session Storage:"
docker exec laravel-inventory ls -la /var/www/html/storage/framework/sessions/ 2>/dev/null || echo "File sessions not found"

echo ""
echo "🗄️  Database Sessions:"
docker exec laravel-inventory php artisan tinker --execute="echo 'Session records: ' . DB::table('sessions')->count();" 2>/dev/null || echo "Database sessions not accessible"

echo ""
echo "🔧 Suggested Actions:"
echo "1. Clear browser cache and cookies for localhost:8080"
echo "2. Try incognito/private browsing mode"
echo "3. Ensure you're using http://localhost:8080 (not https)"
echo "4. Check browser console for JavaScript errors"