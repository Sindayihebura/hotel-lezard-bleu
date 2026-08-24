FROM php:8.2-apache

# Extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql mysqli intl mbstring

# Activer mod_rewrite Apache
RUN a2enmod rewrite

# Copier tout le projet
COPY . /var/www/html/

# Définir le document root sur le dossier public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|g' \
    /etc/apache2/sites-available/000-default.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/cache \
    && chmod -R 777 /var/www/html/storage

# Apache config pour autoriser .htaccess
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

EXPOSE 80
