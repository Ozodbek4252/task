# FROM php:8.3-fpm-alpine
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    bash \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    zlib1g-dev \
    librabbitmq-dev \
    imagemagick \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


# 🛠️ Configure GD with JPEG + FreeType
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    sockets

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

RUN echo "upload_max_filesize=20M\npost_max_size=20M" > /usr/local/etc/php/conf.d/uploads.ini
