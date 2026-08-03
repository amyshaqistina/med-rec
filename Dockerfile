FROM php:8.4-cli

# Install system dependencies + Node
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install pdo pdo_pgsql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy all app files
COPY . .

# Install PHP dependencies FIRST (creates vendor/ folder)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install & build frontend AFTER vendor/ exists
RUN npm install && npm run build

# Set permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan config:cache && php artisan migrate --force && php artisan serve --host 0.0.0.0 --port $PORT
