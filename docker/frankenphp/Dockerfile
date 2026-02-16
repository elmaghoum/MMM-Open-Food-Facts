FROM dunglas/frankenphp:1-php8.4-bookworm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    curl \
    && docker-php-ext-install \
    pdo_pgsql \
    intl \
    zip \
    opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer 2.9
COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer

# Install Node.js 20 LTS
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

# Set working directory
WORKDIR /app

# Copy dependency files first (Docker layer caching)
COPY composer.json composer.lock symfony.lock ./
COPY package.json package-lock.json ./

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Install Node dependencies
RUN npm ci

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Build frontend assets
RUN npm run build

# Set correct permissions
RUN chown -R www-data:www-data /app/var /app/public \
    && chmod -R 755 /app/var /app/public

# Expose ports
EXPOSE 80 443 2019

# Start FrankenPHP with Caddy config
CMD ["frankenphp", "run", "--config", "/app/docker/frankenphp/Caddyfile"]