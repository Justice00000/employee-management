# Use an official PHP runtime as a parent image
FROM php:8.3-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create a non-root user
RUN useradd -m appuser
WORKDIR /var/www/html

# Copy Composer files
COPY --chown=appuser:appuser composer.json composer.lock* ./

# Install dependencies
RUN chmod 755 composer.json && \
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-scripts --no-progress --prefer-dist

# Copy the rest of the application
COPY --chown=appuser:appuser . .

# Adjust permissions
RUN chown -R appuser:appuser /var/www/html

# Enable Apache modules
RUN a2enmod rewrite

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]