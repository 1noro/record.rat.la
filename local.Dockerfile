FROM trafex/php-nginx
USER root
RUN apk add --no-cache php8-fileinfo
USER nobody
# COPY public/ /var/www/html/
COPY docker/local/nginx/record.localhost.conf /etc/nginx/conf.d/server.conf
COPY docker/local/php/settings.ini /etc/php8/conf.d/settings.ini

