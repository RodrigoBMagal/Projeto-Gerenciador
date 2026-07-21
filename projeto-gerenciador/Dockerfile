FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
        bash \
        git \
        curl \
        $PHPIZE_DEPS \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        mysql-client \
    && docker-php-ext-install pdo pdo_mysql mbstring zip bcmath opcache \
    # PCOV: driver de cobertura de codigo (mais leve/rapido que Xdebug),
    # usado por `composer test-coverage` e pelo pipeline de CI.
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /var/www/html

RUN if [ -f composer.json ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction --no-progress; \
    fi \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini

EXPOSE 9000

CMD ["php-fpm"]
