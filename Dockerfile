FROM trafex/php-nginx:2.6.0 AS base
USER root
RUN rm /var/www/html/test.html
RUN apk add --no-cache php8-fileinfo
USER nobody

FROM base AS local
COPY docker/local/nginx/record.localhost.conf /etc/nginx/conf.d/server.conf
COPY docker/local/php/settings.ini /etc/php8/conf.d/settings.ini
COPY docker/local/php/fpm.conf /etc/php8/php-fpm.d/www.conf

FROM local AS sitemapgen
COPY public/ /var/www/html/
USER root
RUN apk add --no-cache curl
USER nobody
COPY docker/sitemap-generator/generate-sitemap.php /var/www/html/generate-sitemap.php
COPY docker/sitemap-generator/sitemap-config.php /var/www/html/sitemap-config.php
COPY docker/sitemap-generator/sitemap-generator.php /var/www/html/sitemap-generator.php
# CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

FROM base AS prod
COPY public/ /var/www/html/
COPY docker/prod/nginx/record.rat.la.conf /etc/nginx/conf.d/server.conf
# COPY docker/prod/php/settings.ini /etc/php8/conf.d/settings.ini
# COPY docker/prod/php/fpm.conf /etc/php8/php-fpm.d/www.conf
