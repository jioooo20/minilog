# =============================================================================
# Minilog — Laravel 12 Dockerfile
# Multi-stage build: PHP + Composer + Node (builder) → PHP-FPM + Nginx (runner)
# Port: 80 internal — akses dari luar via port mapping (misal -p 8001:80)
# =============================================================================

# ---- Stage 1: Build dependencies & assets ----
FROM php:8.3-cli-alpine AS builder

ARG APP_ENV=production

# System dependencies for building
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    nodejs \
    npm

# PHP extensions yg dibutuhkan oleh Laravel (hanya yg TIDAK built-in)
# Catatan: pdo, pdo_sqlite, mbstring, xml, ctype, fileinfo, json, tokenizer
# sudah built-in di php:8.3-cli-alpine dan TIDAK perlu diinstall ulang
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    zip \
    gd

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy dependency manifests first (untuk Docker layer caching)
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-suggest \
    --optimize-autoloader \
    --prefer-dist

# Copy seluruh source code
COPY . .

# Build frontend assets (Vite + Vue)
RUN npm ci --no-audit --no-fund && \
    npm run build && \
    rm -rf node_modules

# Bersihkan .env (akan dibuat ulang di runtime)
RUN rm -rf .env


# ---- Stage 2: Production runtime (PHP-FPM + Nginx) ----
FROM php:8.3-fpm-alpine AS runner

ARG APP_ENV=production
ENV APP_ENV=${APP_ENV}

# Install Nginx, supervisor, dan runtime PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    zip \
    gd \
    opcache \
    && apk del --no-cache libzip-dev oniguruma-dev libxml2-dev freetype-dev libjpeg-turbo-dev libpng-dev

# Copy PHP configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/minilog.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-minilog.conf

# Copy Nginx configuration
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Copy Supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Copy aplikasi dari builder stage
COPY --from=builder /app /var/www/html

# Set permission untuk storage & cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

WORKDIR /var/www/html

EXPOSE 80

ENTRYPOINT ["docker-entrypoint"]
