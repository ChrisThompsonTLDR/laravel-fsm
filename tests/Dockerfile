FROM php:8.3-cli

# Install required extensions for Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql zip

# Copy your application
COPY . /app
WORKDIR /app

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Run mutation testing
CMD ["php", "./vendor/bin/pest", "--mutate", "--everything", "--covered-only", "--parallel", "--min=85"]
