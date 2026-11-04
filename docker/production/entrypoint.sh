#!/bin/sh
# 容器啟動：快取設定、更新資料庫結構，再交給 supervisor 執行所有程序。
set -e

cd /var/www/html

# nginx 不讀環境變數，啟動時把 Render 指定的埠寫進設定檔。
sed "s/__PORT__/${PORT:-10000}/" /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Render 的 Secret File 只有 root 能讀，但網站與 Queue 以 www-data 執行。
# 把 CA 憑證複製到 www-data 也能讀的位置，必須在 config:cache 之前完成。
if [ -n "$MYSQL_ATTR_SSL_CA" ] && [ -f "$MYSQL_ATTR_SSL_CA" ]; then
    install -m 0644 "$MYSQL_ATTR_SSL_CA" /usr/local/share/mysql-ca.pem
    export MYSQL_ATTR_SSL_CA=/usr/local/share/mysql-ca.pem
fi

# 設定快取必須在執行期做，才會帶入 Render 提供的環境變數。
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force
# 種子資料可重複執行，已存在的咖啡豆與規格不會重複建立。
php artisan db:seed --force

exec supervisord -c /etc/supervisord.conf
