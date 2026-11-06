# ==============================================================================
# Multi-stage Dockerfile for Laravel with PHP 8.4 on Render
# ==============================================================================

# --- Stage 1: Build Frontend Assets with Node ---
FROM node:22-alpine AS node_builder
WORKDIR /app
COPY package*.json ./
RUN npm ci || npm install
COPY . .
RUN npm run build

# --- Stage 2: PHP 8.4 Production Environment ---
FROM php:8.4-apache-bookworm

WORKDIR /var/www/html

# Install system dependencies & PHP extension libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libsqlite3-dev \
    sqlite3 \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_sqlite \
        bcmath \
        intl \
        zip \
        opcache

# Enable Apache rewrite and headers modules
RUN a2enmod rewrite headers

# Configure Apache DocumentRoot to Laravel /public
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application source code
COPY . /var/www/html

# Copy compiled frontend assets from node_builder stage
COPY --from=node_builder /app/public/build /var/www/html/public/build

# Install Composer dependencies (production only, no dev dependencies)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy entrypoint script, strip potential CRLF line-endings from Windows, and make executable
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

# Ensure storage, cache, and database directories exist and have proper permissions
RUN mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/cache/data \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/bootstrap/cache \
    && mkdir -p /var/www/html/database \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database /var/www/html/public \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
    && chmod -R 755 /var/www/html/public

# Expose ports (Render maps PORT environment variable dynamically)
EXPOSE 80 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

