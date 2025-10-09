FROM ubuntu:22.04

# Set environment variables to avoid interactive prompts
ENV DEBIAN_FRONTEND=noninteractive

# Install dependencies with retry logic
RUN apt-get update && \
    apt-get install -y \
    software-properties-common \
    curl \
    wget \
    git \
    unzip \
    && add-apt-repository ppa:ondrej/php -y \
    && apt-get update \
    && apt-get install -y \
    php8.2 \
    php8.2-cli \
    php8.2-common \
    php8.2-curl \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-zip \
    php8.2-mysql \
    php8.2-bcmath \
    php8.2-fpm \
    php8.2-gd \
    php8.2-sqlite3 \
    apache2 \
    libapache2-mod-php8.2 \
    && apt-get clean

# Configure Apache
RUN a2enmod rewrite php8.2
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Install Composer with retry
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    || curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configure Composer with mirrors and retries
RUN composer config -g repo.packagist composer https://packagist.org \
    && composer config -g process-timeout 2000 \
    && composer clearcache

WORKDIR /var/www/html

# Copy application code first
COPY . .

# Install dependencies with network retry and fallback to existing vendor
RUN if [ -d "vendor" ]; then \
        echo "Using existing vendor directory"; \
    else \
        echo "Installing composer dependencies..."; \
        composer config -g repos.packagist composer https://packagist.org && \
        composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
        || composer install --no-dev --optimize-autoloader --no-interaction --prefer-source \
        || echo "Warning: Composer install failed, continuing with existing files"; \
    fi

# Laravel setup - copy .env file
COPY .env.docker .env

# Create SQLite database and set permissions
RUN touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

# Generate application key and run migrations
RUN php artisan key:generate --force \
    && php artisan migrate --force

# Add startup and health check scripts
COPY docker/startup.sh /usr/local/bin/startup.sh
COPY docker/health-check.sh /usr/local/bin/health-check.sh
RUN chmod +x /usr/local/bin/startup.sh /usr/local/bin/health-check.sh

# Configure Apache ServerName to suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80

# Add health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

CMD ["/usr/local/bin/startup.sh"]