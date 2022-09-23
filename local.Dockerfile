FROM trafex/php-nginx
USER root
RUN apk add --no-cache php8-fileinfo
USER nobody
# COPY public/ /var/www/html/
COPY docker/local/nginx/record.localhost.conf /etc/nginx/conf.d/server.conf
COPY docker/local/php/settings.ini /etc/php8/conf.d/settings.ini
COPY docker/local/php/fpm.conf /etc/php8/php-fpm.d/www.conf
