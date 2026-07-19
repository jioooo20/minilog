#!/bin/sh
# =============================================================================
# Docker entrypoint for Minilog (Laravel)
# Handles environment setup before starting services
# =============================================================================

set -e

# If .env doesn't exist, create from .env.example
if [ ! -f /var/www/html/.env ]; then
    echo "==> Creating .env"
    cp /var/www/html/.env.example /var/www/html/.env
fi

if ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
    echo "==> Generating APP_KEY"
    php artisan key:generate --force
fi

# Ensure storage directories exist and have correct permissions
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/database
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Run migrations (if DB is ready)
DB_FILE=/var/www/html/storage/database.sqlite

if [ ! -f "$DB_FILE" ]; then
    echo "==> Creating database"

    touch "$DB_FILE"
    chown www-data:www-data "$DB_FILE"

    php artisan migrate:fresh --seed --force
else
    echo "==> Running normal migration"

    php artisan migrate --force
fi

# Clear and cache config
php /var/www/html/artisan config:cache --ansi 2>/dev/null || true
php /var/www/html/artisan route:cache --ansi 2>/dev/null || true
php /var/www/html/artisan view:cache --ansi 2>/dev/null || true

echo "==> Starting Minilog on port 80"

# Buat directory untuk supervisor log
mkdir -p /var/log/supervisor

# Start supervisord (which manages nginx + php-fpm)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
