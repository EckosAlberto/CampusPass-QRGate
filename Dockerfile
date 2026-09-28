FROM php:8.4-apache
RUN apt-get update && apt-get install -y git unzip libzip-dev libpng-dev libonig-dev libxml2-dev nodejs npm \
    && docker-php-ext-install pdo_mysql mbstring zip gd
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
RUN npm ci && npm run build
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
RUN chown -R www-data:www-data storage bootstrap/cache
EXPOSE 80
CMD bash -c "a2dismod mpm_event mpm_worker || true; a2enmod mpm_prefork rewrite || true; php artisan config:clear; php artisan route:clear; php artisan view:clear; php artisan migrate --force || true; apache2-foreground"