# Imagem de produção/homologação: FrankenPHP (Caddy + PHP) escutando em :8080.
# O Caddy da borda da VPS faz o HTTPS e o reverse_proxy para este container.

# 1) Dependências PHP (sem dev)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

# 2) Assets (Vite/Tailwind). Precisa do vendor (Livewire ESM + views escaneadas pelo Tailwind).
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --ignore-scripts
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# 3) Runtime
FROM dunglas/frankenphp:1-php8.5 AS app
RUN install-php-extensions pdo_mysql intl gd zip bcmath exif opcache pcntl
ENV APP_ENV=production APP_DEBUG=false SERVER_NAME=:8080
WORKDIR /app

COPY --from=vendor /usr/bin/composer /usr/bin/composer
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build
COPY deploy/Caddyfile /etc/caddy/Caddyfile
COPY deploy/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY deploy/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public storage/app/private bootstrap/cache \
 && APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= composer dump-autoload --optimize --no-interaction \
 && chown -R www-data:www-data storage bootstrap/cache public

USER www-data
EXPOSE 8080
ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
