# ================================================================================ #
#                         Author: Abderrahmane Abdelouafi                          #
#                              File Name: dockerfile                               #
#                      Creation Date: April 12, 2026 01:07 PM                      #
#                      Last Updated: August 14, 2026                              #
#                           Source Language: dockerfile                            #
#                                                                                  #
#                             --- Code Description ---                             #
#  Dockerfile for Apache image with PHP 8.4, required extensions, SSL, Composer.   #
# ================================================================================ #

FROM php:8.4-apache-bookworm

# Install system dependencies (including oniguruma for mbstring)
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    openssl \
    curl \
    libonig-dev \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql mbstring \
    && a2enmod rewrite ssl headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy entrypoint script
COPY tools/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Copy SSL configuration
COPY config/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf

# Enable the SSL site and disable default
RUN a2dissite 000-default \
    && rm -f /etc/apache2/sites-available/000-default.conf \
    && a2ensite default-ssl

# Create directories for Composer and env
RUN mkdir -p /var/www/composer /var/www/env

# Install Composer and required PHP packages
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN composer require google/apiclient:"^2.1" \
    phpmailer/phpmailer \
    --working-dir=/var/www/composer \
    --no-interaction \
    --prefer-dist

EXPOSE 80 443

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
