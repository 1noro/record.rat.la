FROM trafex/php-nginx
COPY public/ /var/www/html/
COPY docker/local/nginx/record.localhost.conf /etc/nginx/conf.d/server.conf
