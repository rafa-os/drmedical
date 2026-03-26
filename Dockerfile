FROM php:8.1-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN a2dismod mpm_event && a2enmod mpm_prefork

COPY . /var/www/html/

RUN echo 'Listen ${PORT}' > /etc/apache2/ports.conf \
    && sed -i 's/VirtualHost \*:80/VirtualHost *:${PORT}/' /etc/apache2/sites-enabled/000-default.conf

RUN chown -R www-data:www-data /var/www/html

CMD ["apache2-foreground"]
