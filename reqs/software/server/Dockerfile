# ================================================================================ #
#                         Author: Abderrahmane Abdelouafi                          #
#                              File Name: dockerfile                               #
#                      Creation Date: April 12, 2026 01:07 PM                      #
#                      Last Updated: August 14, 2026 01:07 PM                      #
#                           Source Language: dockerfile                            #
#                                                                                  #
#                             --- Code Description ---                             #
#      Dockerfile for setting up a PHP 8.4 Apache server with SSL support and      #
#                              necessary dependencies.                             #
# ================================================================================ #

FROM php:8.4-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        unzip \
        openssl \
        curl \
        libonig-dev \
    && docker-php-ext-install \
        -j"$(nproc)" \
        pdo \
        pdo_mysql \
        mbstring \
    && a2enmod \
        rewrite \
        ssl \
        headers \
        reqtimeout \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY tools/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod 755 /usr/local/bin/docker-entrypoint.sh

COPY config/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf.template
COPY config/security.conf /etc/apache2/conf-available/ias42-security.conf
COPY config/php-security.ini /usr/local/etc/php/conf.d/99-ias42-security.ini

RUN cp /etc/apache2/sites-available/default-ssl.conf.template \
        /etc/apache2/sites-available/default-ssl.conf \
    && a2dissite 000-default \
    && rm -f /etc/apache2/sites-available/000-default.conf \
    && a2ensite default-ssl \
    && a2enconf ias42-security

RUN mkdir -p \
    /var/www/composer \
    /var/www/env

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN composer require \
        google/apiclient:"^2.1" \
        phpmailer/phpmailer \
        php-mqtt/client:"2.3.2" \
        --working-dir=/var/www/composer \
        --no-interaction \
        --prefer-dist

EXPOSE 80 443

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["apache2-foreground"]
