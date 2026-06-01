FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libsqlite3-dev \
        libzip-dev \
        libonig-dev \
        unzip \
        ca-certificates \
    && docker-php-ext-install -j$(nproc) pdo_sqlite zip mbstring \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}/!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN printf '%s\n' \
    '<Directory /var/www/html/public>' \
    '    Options Indexes FollowSymLinks' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/vagas-rj.conf \
    && a2enconf vagas-rj

WORKDIR /var/www/html

COPY . /var/www/html/

RUN mkdir -p /var/www/html/database /var/www/html/storage /var/www/html/storage/imports \
    && chown -R www-data:www-data /var/www/html/database /var/www/html/storage \
    && chmod -R 775 /var/www/html/database /var/www/html/storage

EXPOSE 80

CMD ["apache2-foreground"]