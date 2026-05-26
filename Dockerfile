FROM php:8.3-cli-alpine

RUN apk add --no-cache \
        sqlite \
        sqlite-dev \
        oniguruma-dev \
        libzip-dev \
        unzip \
        git \
    && docker-php-ext-install pdo pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --optimize-autoloader || composer install --no-interaction --optimize-autoloader

COPY . .

RUN mkdir -p /var/www/html/database \
    && chmod -R 0777 /var/www/html/database

EXPOSE 8080

CMD ["sh", "-c", "php database/init.php && php -S 0.0.0.0:8080 -t public public/router.php"]
