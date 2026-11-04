#!/bin/bash
set -e

echo "Ajustando permisos de carpetas..."

mkdir -p /var/www/html/sigsm/uploads /var/www/html/sigsm/temp_qr /var/www/html/sigsm/config /etc/apache2/ssl

if [ ! -f /etc/apache2/ssl/server.crt ] || [ ! -f /etc/apache2/ssl/server.key ]; then
    openssl req -x509 -nodes -newkey rsa:2048 \
        -keyout /etc/apache2/ssl/server.key \
        -out /etc/apache2/ssl/server.crt \
        -days 365 \
        -subj "/CN=localhost"
fi

chown -R www-data:www-data /var/www/html/sigsm/uploads || true
chmod -R 777 /var/www/html/sigsm/uploads

chown -R www-data:www-data /var/www/html/sigsm/temp_qr || true
chmod -R 777 /var/www/html/sigsm/temp_qr

chown -R www-data:www-data /var/www/html/sigsm/config || true
chmod -R 777 /var/www/html/sigsm/config

chown root:www-data /etc/apache2/ssl/server.key /etc/apache2/ssl/server.crt || true
chmod 600 /etc/apache2/ssl/server.key
chmod 644 /etc/apache2/ssl/server.crt

exec apache2-foreground