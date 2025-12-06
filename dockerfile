FROM php:8.2-fpm-alpine

# Install only essential dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl

# Set PHP configuration for file uploads
RUN echo "post_max_size = 20M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "upload_max_filesize = 20M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 120" >> /usr/local/etc/php/conf.d/uploads.ini

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . /var/www

# Set permissions
RUN chown -R www-data:www-data /var/www

# Install composer dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Create storage directories
RUN mkdir -p /var/www/storage/app/public/documents && \
    mkdir -p /var/www/storage/framework/cache && \
    mkdir -p /var/www/storage/framework/sessions && \
    mkdir -p /var/www/storage/framework/views && \
    mkdir -p /var/www/storage/logs && \
    chown -R www-data:www-data /var/www/storage && \
    chmod -R 775 /var/www/storage

# Create symbolic link for storage
RUN php artisan storage:link || true

EXPOSE 9000
CMD ["php-fpm"]
