FROM php:8.3-apache-bookworm AS base

# Install PHP extensions required by openSIS
RUN apt-get update && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        libicu-dev \
        zlib1g-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mysqli \
        gd \
        zip \
        intl \
        opcache \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# PHP production settings
RUN { \
        echo 'upload_max_filesize = 20M'; \
        echo 'post_max_size = 20M'; \
        echo 'max_execution_time = 300'; \
        echo 'memory_limit = 256M'; \
        echo 'session.cookie_httponly = 1'; \
        echo 'session.cookie_samesite = Strict'; \
        echo 'date.timezone = UTC'; \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /var/log/php_errors.log'; \
    } > /usr/local/etc/php/conf.d/opensis.ini

# OPcache settings for performance
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Apache: allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
    /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copy application
COPY --chown=www-data:www-data . /var/www/html/

# Ensure writable dirs
RUN mkdir -p /var/www/html/tmp \
    && chown -R www-data:www-data /var/www/html/tmp

EXPOSE 80

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

CMD ["apache2-foreground"]
