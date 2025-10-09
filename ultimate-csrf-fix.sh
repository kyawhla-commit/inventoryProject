#!/bin/bash

echo "🚀 Ultimate CSRF Fix - Comprehensive Solution"
echo "============================================="

echo "1️⃣  Stopping container..."
docker stop laravel-inventory

echo "2️⃣  Updating environment configuration..."
# Ensure the most compatible settings
cat > .env.docker << 'EOF'
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080
SANCTUM_STATEFUL_DOMAINS=localhost:8080,127.0.0.1:8080

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

SESSION_DRIVER=file
SESSION_LIFETIME=7200
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_COOKIE=laravel_session

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=file

# Dashboard
MONTHLY_SALES_GOAL=10000

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
EOF

echo "3️⃣  Starting container..."
docker start laravel-inventory

echo "4️⃣  Copying updated configuration..."
docker cp .env.docker laravel-inventory:/var/www/html/.env

echo "5️⃣  Clearing all caches..."
docker exec laravel-inventory php artisan config:clear
docker exec laravel-inventory php artisan cache:clear
docker exec laravel-inventory php artisan view:clear
docker exec laravel-inventory php artisan route:clear

echo "6️⃣  Regenerating application key..."
docker exec laravel-inventory php artisan key:generate --force

echo "7️⃣  Clearing session storage..."
docker exec laravel-inventory rm -rf /var/www/html/storage/framework/sessions/*
docker exec laravel-inventory rm -rf /var/www/html/storage/framework/cache/*
docker exec laravel-inventory rm -rf /var/www/html/storage/framework/views/*

echo "8️⃣  Setting proper permissions..."
docker exec laravel-inventory chown -R www-data:www-data /var/www/html/storage
docker exec laravel-inventory chmod -R 775 /var/www/html/storage

echo "9️⃣  Final restart..."
docker restart laravel-inventory

echo ""
echo "✅ Ultimate CSRF fix completed!"
echo ""
echo "🔧 Now do the following:"
echo "   1. Close ALL browser windows/tabs"
echo "   2. Clear browser cache and cookies completely"
echo "   3. Open a new incognito/private window"
echo "   4. Go to http://localhost:8080"
echo "   5. Try logging in"
echo ""
echo "🐛 If still having issues:"
echo "   - Check browser console for errors (F12)"
echo "   - Try a different browser"
echo "   - Run: ./debug-csrf.sh"