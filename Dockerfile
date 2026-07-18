# =============================================================================
# Minilog — Laravel 12 Dockerfile
# Multi-stage build: PHP + Composer + Node (builder) → PHP-FPM + Nginx (runner)
# Exposed port: 80
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
    sqlite-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    nodejs \
    npm

# Configure the GD extension to use the image libraries we just installed
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# PHP extensions required by Laravel (Removed json and tokenizer)
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_sqlite \
    mbstring \
    xml \
    bcmath \
    ctype \
    fileinfo \
    zip \
    gd

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy dependency manifests first (for Docker layer caching)
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-suggest \
    --optimize-autoloader \
    --prefer-dist

# Copy the rest of the application
COPY . .

# Build frontend assets
RUN npm ci --no-audit --no-fund && \
    npm run build && \
    rm -rf node_modules

# Clean up
RUN rm -rf .env


# ---- Stage 2: Production runtime (PHP-FPM + Nginx) ----
FROM php:8.3-fpm-alpine AS runner

ARG APP_ENV=production
ENV APP_ENV=${APP_ENV}

# Install Nginx, supervisor, and runtime PHP extensions
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
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_sqlite \
    mbstring \
    xml \
    bcmath \
    ctype \
    fileinfo \
    json \
    tokenizer \
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

# Copy application files from builder stage
COPY --from=builder /app /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

WORKDIR /var/www/html

EXPOSE 80

ENTRYPOINT ["docker-entrypoint"]
