#!/bin/bash

echo "🔧 Fixing CSRF token issues..."

# Clear all caches
docker exec laravel-inventory php artisan config:clear
docker exec laravel-inventory php artisan cache:clear
docker exec laravel-inventory php artisan view:clear
docker exec laravel-inventory php artisan route:clear

# Regenerate application key
docker exec laravel-inventory php artisan key:generate --force

# Restart the container to ensure fresh session
docker restart laravel-inventory

echo "✅ CSRF fix applied. Please try logging in again."
echo "💡 If you still get 419 errors:"
echo "   1. Clear your browser cache and cookies for localhost:8080"
echo "   2. Try using an incognito/private browsing window"
echo "   3. Make sure you're accessing http://localhost:8080 (not https)"