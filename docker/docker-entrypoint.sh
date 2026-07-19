#!/bin/sh
# =============================================================================
# Docker entrypoint for Minilog (Laravel)
# Handles environment setup before starting services
# =============================================================================

set -e

# If .env doesn't exist, create from .env.example
if [ ! -f /var/www/html/.env ]; then
    echo "==> Creating .env from .env.example"
    cp /var/www/html/.env.example /var/www/html/.env

    # Generate app key
    php /var/www/html/artisan key:generate --force
fi

# Ensure storage directories exist and have correct permissions
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Run migrations (if DB is ready)
php /var/www/html/artisan migrate --force 2>/dev/null || echo "==> Migration skipped (DB not ready yet)"

# Clear and cache config
php /var/www/html/artisan config:cache --ansi 2>/dev/null || true
php /var/www/html/artisan route:cache --ansi 2>/dev/null || true
php /var/www/html/artisan view:cache --ansi 2>/dev/null || true

echo "==> Starting Minilog on port 80"

# Buat directory untuk supervisor log
mkdir -p /var/log/supervisor

# Start supervisord (which manages nginx + php-fpm)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
