#!/bin/sh
set -e

# 1. Adapt Apache port to Render dynamic $PORT (default 80 or 10000)
PORT="${PORT:-80}"
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# 2. Ensure .env file exists and has APP_KEY
if [ ! -f /var/www/html/.env ]; then
    if [ -f /var/www/html/.env.example ]; then
        cp /var/www/html/.env.example /var/www/html/.env
    else
        touch /var/www/html/.env
    fi
fi

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# 3. Ensure SQLite database file exists
if [ ! -f /var/www/html/database/database.sqlite ]; then
    mkdir -p /var/www/html/database
    touch /var/www/html/database/database.sqlite
fi

# 4. Set proper permissions for storage, cache, and database
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database /var/www/html/.env
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# 5. Run database migrations and initial seeders
php artisan migrate --force --seed

# 6. Optimize Laravel caches for production performance
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# 7. Start Apache in foreground
exec apache2-foreground

