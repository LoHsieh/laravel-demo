# 正式環境映像（Render，ADR 0002）。本機開發用的映像在 docker/php/Dockerfile。
# 一個容器內由 supervisor 同時執行 nginx、php-fpm、schedule:work 與 queue:work。

# ---- 前端資源 ----
FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ---- 執行環境 ----
FROM php:8.4-fpm-alpine

RUN apk add --no-cache nginx supervisor libzip-dev \
    && docker-php-ext-install pdo_mysql zip pcntl opcache \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/production/php.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/production/php-fpm.conf /usr/local/etc/php-fpm.d/zz-production.conf
COPY docker/production/nginx.conf /etc/nginx/nginx.conf.template
COPY docker/production/supervisord.conf /etc/supervisord.conf
COPY docker/production/entrypoint.sh /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

# 先只裝相依套件，讓程式碼變動時能沿用這一層快取。
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist

COPY . .
COPY --from=assets /app/public/build ./public/build

# dump-autoload 會觸發 post-autoload-dump，順帶完成 package:discover。
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x /usr/local/bin/entrypoint.sh

# Render 以 PORT 環境變數告知要監聽的埠，預設 10000。
ENV PORT=10000
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
