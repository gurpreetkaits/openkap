FROM php:8.3-fpm-alpine

ARG USER_ID=1000
ARG GROUP_ID=1000

# System deps + PHP extension build deps in ONE layer for maximum cache reuse
# ffmpeg is needed for video processing in both web and queue contexts
RUN apk add --no-cache \
        ffmpeg \
        libpng \
        libzip \
        oniguruma \
        libxml2 \
        icu-libs \
    && apk add --no-cache --virtual .build-deps \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        libxml2-dev \
        icu-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
        intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear /var/cache/apk/*

# Composer binary only
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Non-root user
RUN addgroup -g ${GROUP_ID} appgroup \
    && adduser -u ${USER_ID} -G appgroup -D appuser \
    && mkdir -p /var/log/php \
    && chown appuser:appgroup /var/log/php

WORKDIR /var/www/html

# In dev, code is bind-mounted so we skip COPY and composer install.
# Just ensure storage/bootstrap dirs exist with correct permissions.
RUN mkdir -p storage/framework/{cache,sessions,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R appuser:appgroup storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER appuser

EXPOSE 9000
CMD ["php-fpm"]
