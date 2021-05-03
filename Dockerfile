FROM harbor.zierhut-it.de/public/php:8.0-apache

COPY --chown=apache publish /var/www/html/
