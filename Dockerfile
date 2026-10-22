# The image of the API on Coolify. FrankenPHP serves HTTP, and supervisor runs
# the queue worker and the scheduler next to it, all from this one image.
# The tag is pinned so that a rebuild cannot reuse an older base image cached
# on the server. Raise it whenever PHP or FrankenPHP ships a security release.
FROM dunglas/frankenphp:1.13.2-php8.4.27

ENV COMPOSER_ALLOW_SUPERUSER=1

# The PHP extensions the app needs on top of the base image, and supervisor.
RUN install-php-extensions \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && apt-get update \
    && apt-get install -y --no-install-recommends supervisor \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

WORKDIR /app

# Dependencies are installed before the code is copied, so this layer is
# reused until composer.lock changes.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .

# Building the autoloader also runs the Composer scripts, which discover the
# packages and publish Filament's assets into public/.
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && composer dump-autoload --no-dev --optimize --no-interaction

# Coolify's proxy ends TLS, so FrankenPHP serves plain HTTP on port 80.
# Logs go to stderr, where Coolify collects them.
ENV SERVER_NAME=:80 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=5s --start-period=120s --retries=3 \
    CMD curl -fsS http://localhost/up || exit 1

CMD ["start-container"]
