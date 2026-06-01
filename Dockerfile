FROM php:8.2-apache

# Extensões: pdo/pdo_sqlite/sqlite3 (import XLSX), zip, mbstring; simplexml já vem no PHP.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev libzip-dev \
    && docker-php-ext-install pdo_sqlite sqlite3 zip mbstring \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

COPY . /var/www/html

RUN mkdir -p database storage/imports storage/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 database storage

COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
