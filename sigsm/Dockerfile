FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html \
    APP_FORCE_HTTPS=false \
    APP_ENV=development

RUN set -eux; \
    apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        default-mysql-client \
        libsqlite3-dev \
        openssl \
        cron \
    && rm -rf /var/lib/apt/lists/*; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" gd pdo pdo_mysql pdo_sqlite; \
    a2enmod rewrite headers alias expires deflate ssl

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!<Directory /var/www/html>!<Directory ${APACHE_DOCUMENT_ROOT}>!g' /etc/apache2/apache2.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && printf '%s\n' 'Listen 80' 'Listen 443' > /etc/apache2/ports.conf \
    && printf '%s\n' '<VirtualHost *:80>' '    ServerName _default_' '    DocumentRoot /var/www/html' '    Alias /sigsm /var/www/html/sigsm' '    <Directory /var/www/html>' '        Options FollowSymLinks' '        AllowOverride All' '        Require all granted' '    </Directory>' '    <Directory /var/www/html/sigsm>' '        Options FollowSymLinks' '        AllowOverride All' '        Require all granted' '    </Directory>' '</VirtualHost>' > /etc/apache2/sites-available/000-default.conf \
    && printf '%s\n' '<IfModule mod_ssl.c>' '    <VirtualHost *:443>' '        ServerName _default_' '        DocumentRoot /var/www/html' '        Alias /sigsm /var/www/html/sigsm' '        SSLEngine on' '        SSLCertificateFile /etc/apache2/ssl/server.crt' '        SSLCertificateKeyFile /etc/apache2/ssl/server.key' '        RedirectMatch 302 ^/$ /sigsm/' '        <Directory /var/www/html>' '            Options FollowSymLinks' '            AllowOverride All' '            Require all granted' '        </Directory>' '        <Directory /var/www/html/sigsm>' '            Options FollowSymLinks' '            AllowOverride All' '            Require all granted' '        </Directory>' '    </VirtualHost>' '</IfModule>' > /etc/apache2/sites-available/default-ssl.conf \
    && a2ensite default-ssl

COPY config/php-custom.ini /usr/local/etc/php/conf.d/custom.ini
COPY config/apache-custom.conf /etc/apache2/conf-available/servername.conf
RUN a2enconf servername

WORKDIR /var/www/html
COPY . /var/www/html/sigsm

RUN printf '%s\n' '<?php' 'header("Location: /sigsm/");' 'exit;' '?>' > /var/www/html/index.php \
    && mkdir -p /var/www/html/sigsm/uploads \
    /var/www/html/sigsm/temp_qr \
    /var/www/html/sigsm/config/reset_codes \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/sigsm/uploads \
    /var/www/html/sigsm/temp_qr \
    /var/www/html/sigsm/config \
    && chmod 755 /var/www/html \
    /var/www/html/index.php \
    /var/www/html/sigsm

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
COPY backup_task.sh /usr/local/bin/sigsm-backup.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/sigsm-backup.sh \
    && (crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/sigsm-backup.sh >> /var/log/sigsm_backups.log 2>&1") | crontab -

EXPOSE 80 443
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
