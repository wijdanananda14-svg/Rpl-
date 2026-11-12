FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev libmariadb-dev \
    && docker-php-ext-install pdo_sqlite pdo_mysql sqlite3 \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY index.php ./
COPY .htaccess ./
COPY app ./app
COPY public ./public

RUN mkdir -p /var/data \
    && chown -R www-data:www-data /var/data /var/www/html

ENV PORT=10000
ENV APP_ENV=production
ENV SPORTIVA_DB_PATH=/var/data/sportiva.sqlite

CMD sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf \
    && sed -i "s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf \
    && exec apache2-foreground
