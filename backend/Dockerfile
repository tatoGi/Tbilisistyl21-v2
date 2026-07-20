FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    postgresql-client \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        gd \
        intl \
        zip \
        bcmath \
        opcache \
        mbstring \
        exif \
        pcntl

# imagick — required by bacon/qr-code to render ticket QR PNGs (email jobs).
RUN apk add --no-cache imagemagick imagemagick-libs \
    && apk add --no-cache --virtual .imagick-build $PHPIZE_DEPS imagemagick-dev \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apk del .imagick-build

# OPcache production config
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-prod.ini

# Media uploads: Filament allows images up to 5 MB (maxSize 5120), so PHP's
# 2M/8M defaults silently rejected them and records saved without an image.
RUN { \
    echo 'upload_max_filesize=10M'; \
    echo 'post_max_size=25M'; \
} > /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

# Copy application code
COPY . .

# Optimize the autoloader. config/route/view caching happens at runtime via the
# entrypoint (build-time has no env_file vars and would bake wrong config).
RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
