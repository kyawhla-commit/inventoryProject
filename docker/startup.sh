#!/bin/bash

# Laravel Docker Startup Script
echo "🚀 Starting Laravel application..."

# Ensure proper permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Ensure database exists and has proper permissions
if [ ! -f /var/www/html/database/database.sqlite ]; then
    echo "📄 Creating SQLite database..."
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
fi
chmod 664 /var/www/html/database/database.sqlite

# Ensure .env exists
if [ ! -f /var/www/html/.env ]; then
    echo "⚙️  Creating .env file..."
    if [ -f /var/www/html/.env.docker ]; then
        cp /var/www/html/.env.docker /var/www/html/.env
    elif [ -f /var/www/html/.env.example ]; then
        cp /var/www/html/.env.example /var/www/html/.env
    fi
fi

# Generate app key if not set
if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Run migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Clear and cache config
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "✅ Laravel application ready!"

# Start Apache
exec /usr/sbin/apache2ctl -D FOREGROUND