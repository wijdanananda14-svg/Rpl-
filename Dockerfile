FROM dunglas/frankenphp:1-php8.4-alpine

WORKDIR /app

RUN install-php-extensions sqlite3 \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "session.save_path=/tmp" >> "$PHP_INI_DIR/php.ini" \
    && mkdir -p /var/data

COPY index.php ./index.php
COPY app ./app
COPY public ./public
COPY Caddyfile /etc/caddy/Caddyfile

ENV PORT=80
ENV APP_ENV=production
ENV SPORTIVA_DB_PATH=/var/data/sportiva.sqlite

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
