FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    libzip-dev \
    libpq-dev \
    nano \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
WORKDIR /var/www
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Give permissions
RUN chmod -R 755 /var/www
RUN chown -R www-data:www-data /var/www

# Expose port
EXPOSE 8000

# Start Laravel app
CMD php artisan serve --host=0.0.0.0 --port=8000
