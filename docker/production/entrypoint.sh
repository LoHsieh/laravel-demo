#!/bin/sh
# 容器啟動：快取設定、更新資料庫結構，再交給 supervisor 執行所有程序。
set -e

cd /var/www/html

# nginx 不讀環境變數，啟動時把 Render 指定的埠寫進設定檔。
sed "s/__PORT__/${PORT:-10000}/" /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# 設定快取必須在執行期做，才會帶入 Render 提供的環境變數。
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force
# 種子資料可重複執行，已存在的咖啡豆與規格不會重複建立。
php artisan db:seed --force

exec supervisord -c /etc/supervisord.conf
