#!/bin/bash
set -e

mkdir -p /var/www/html/database /var/www/html/storage/imports /var/www/html/storage/logs

chown -R www-data:www-data /var/www/html/database /var/www/html/storage
chmod -R 775 /var/www/html/database /var/www/html/storage

exec apache2-foreground
