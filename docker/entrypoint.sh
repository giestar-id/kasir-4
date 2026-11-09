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

# 3. Ensure SQLite database directory & file exist
DB_FILE="${DB_DATABASE:-/data/database.sqlite}"
DB_DIR=$(dirname "$DB_FILE")

mkdir -p "$DB_DIR"
if [ ! -f "$DB_FILE" ]; then
    touch "$DB_FILE"
fi

# 4. Set proper permissions for storage, cache, database, and public assets
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database /var/www/html/.env /var/www/html/public "$DB_DIR"
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database "$DB_DIR"
chmod 664 "$DB_FILE" 2>/dev/null || true
chmod -R 755 /var/www/html/public

# 5. Run database migrations and initial seeders
php artisan migrate --force --seed

# 6. Optimize Laravel caches & SQLite WAL mode for peak production performance
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true
php artisan event:cache || true
sqlite3 "$DB_FILE" "PRAGMA journal_mode=WAL; PRAGMA synchronous=NORMAL;" || true

# 7. Start Apache in foreground
exec apache2-foreground

