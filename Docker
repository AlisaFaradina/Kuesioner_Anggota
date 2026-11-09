
FROM php:8.3-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2enmod rewrite

COPY . /var/www/html/

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD ["bash", "-c", "sed -i 's/Listen 80/Listen ${PORT:-10000}/' /etc/apache2/ports.conf && sed -i 's/<VirtualHost \\*:80>/<VirtualHost *:${PORT:-10000}>/' /etc/apache2/sites-available/000-default.conf && apache2-foreground"]

