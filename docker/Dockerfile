FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    postgresql-dev \
    nodejs \
    npm \
    supervisor

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache

# Install Redis extension (autoconf required by phpize/pecl, removed after)
RUN apk add --no-cache --virtual .build-deps autoconf g++ make \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy supervisor config
COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create system user for running the application
# RUN addgroup -g 1000 -S www && \
#     adduser -u 1000 -S www -G www

# # Create required directories and set permissions
# RUN mkdir -p /var/log/supervisor \
#     && chown -R www:www /var/log/supervisor \
#     && chown -R www:www /var/www/html

RUN addgroup -g 1000 -S www \
    && adduser -u 1000 -S www -G www \
    && mkdir -p /var/log/supervisor \
    && mkdir -p /var/run \
    # Pre-create the dirs that get overlaid by named volumes (docker-compose),
    # so the empty volumes seed as www-owned and composer/npm can write to them.
    && mkdir -p /var/www/html/vendor /var/www/html/node_modules \
    && chown -R www:www /var/log/supervisor \
    && chown -R www:www /var/run \
    && chown -R www:www /var/www/html

USER www

EXPOSE 9000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]