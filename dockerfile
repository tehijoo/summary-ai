# Use a recommended PHP base image with FPM and Alpine Linux (small)
FROM php:8.2-fpm-alpine

# Install necessary system dependencies and PHP extensions
RUN apk add --no-cache \
    nginx \
    bash \
    mysql-client \
    git \
    openssl \
    && docker-php-ext-install pdo pdo_mysql opcache \
    && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory for the application
WORKDIR /var/www/html

# Copy your application source code (important: do this AFTER installing composer)
# We assume the code is present when building the image
COPY . /var/www/html

# Run composer install to get PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions for Laravel storage
RUN chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Expose PHP-FPM port
EXPOSE 9000

# Start PHP-FPM service
CMD ["php-fpm"]
