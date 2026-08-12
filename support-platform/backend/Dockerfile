FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user
RUN useradd -G www-data,root -u 1000 -d /home/devuser devuser
RUN mkdir -p /home/devuser/.composer && \
    chown -R devuser:devuser /home/devuser/.composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application files
COPY . .

# Set permissions
RUN chown -R devuser:devuser /var/www/html \
    && chmod -R 755 /var/www/html/storage 2>/dev/null || true

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Default command
CMD ["php-fpm"]
