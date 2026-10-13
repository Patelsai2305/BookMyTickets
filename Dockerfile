FROM php:8.2-apache

RUN a2enmod rewrite

RUN docker-php-ext-install curl

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["docker-entrypoint.sh"]
