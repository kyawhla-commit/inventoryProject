#!/bin/bash

echo "🔧 Fixing Empty Response Issues"
echo "==============================="

echo "📊 Checking container status..."
if ! docker ps --filter name=laravel-inventory --format "{{.Names}}" | grep -q laravel-inventory; then
    echo "❌ Container 'laravel-inventory' is not running"
    echo ""
    echo "🔧 Attempting to start existing container..."
    if docker start laravel-inventory 2>/dev/null; then
        echo "✅ Container started successfully"
        sleep 5
    else
        echo "❌ No existing container found"
        echo ""
        echo "🏗️  You need to rebuild the container:"
        echo "   ./docker-manage.sh build"
        echo "   ./docker-manage.sh start"
        echo ""
        echo "💡 If build fails due to network issues:"
        echo "   1. Check your internet connection"
        echo "   2. Try again later"
        echo "   3. Or use a different network"
        exit 1
    fi
fi

echo ""
echo "🧪 Testing basic connectivity..."
if curl -s --max-time 5 http://localhost:8080/ > /dev/null 2>&1; then
    echo "✅ Application is responding"
else
    echo "❌ Application not responding - checking common issues..."
    
    echo ""
    echo "🔍 Checking Apache status..."
    if docker exec laravel-inventory ps aux | grep -q apache2; then
        echo "✅ Apache is running"
    else
        echo "❌ Apache is not running"
        echo "🔧 Restarting Apache..."
        docker exec laravel-inventory service apache2 restart
    fi
    
    echo ""
    echo "🔍 Checking PHP status..."
    if docker exec laravel-inventory php -v > /dev/null 2>&1; then
        echo "✅ PHP is working"
    else
        echo "❌ PHP has issues"
    fi
    
    echo ""
    echo "🔧 Clearing all caches..."
    docker exec laravel-inventory php artisan config:clear
    docker exec laravel-inventory php artisan cache:clear
    docker exec laravel-inventory php artisan view:clear
    
    echo ""
    echo "🔧 Fixing permissions..."
    docker exec laravel-inventory chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
    docker exec laravel-inventory chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
    
    echo ""
    echo "🔄 Restarting container..."
    docker restart laravel-inventory
    sleep 10
fi

echo ""
echo "🧪 Final connectivity test..."
if curl -s --max-time 10 http://localhost:8080/ > /dev/null 2>&1; then
    echo "✅ Application is now responding!"
    echo "🌐 Try accessing: http://localhost:8080"
    echo "🔑 Login with: admin@example.com / password"
else
    echo "❌ Application still not responding"
    echo ""
    echo "🔍 Diagnostic information:"
    echo "Container status:"
    docker ps --filter name=laravel-inventory
    echo ""
    echo "Container logs (last 10 lines):"
    docker logs laravel-inventory --tail 10
    echo ""
    echo "💡 Manual troubleshooting steps:"
    echo "   1. Check container logs: docker logs laravel-inventory"
    echo "   2. Access container shell: ./docker-manage.sh shell"
    echo "   3. Rebuild container: ./docker-manage.sh rebuild"
fi