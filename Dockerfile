FROM php:8.1-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN rm -f /etc/apache2/mods-enabled/mpm_event.conf \
    && rm -f /etc/apache2/mods-enabled/mpm_event.load \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.conf \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.load \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load

RUN echo 'Listen ${PORT}' > /etc/apache2/ports.conf \
    && sed -i 's/VirtualHost \*:80/VirtualHost *:${PORT}/' /etc/apache2/sites-enabled/000-default.conf

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

CMD ["apache2-foreground"]
