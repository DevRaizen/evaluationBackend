FROM composer:2 AS composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

FROM php:8.3-cli

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY php.ini /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

COPY . .

COPY --from=composer /app/vendor ./vendor

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app"]