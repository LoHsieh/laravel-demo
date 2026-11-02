# 焙日 Bairi

精品咖啡豆網路商店。Laravel + MySQL，前台以 Blade 渲染並嵌入 Vue 元件，由 Vite 打包。

## 本機開發

需要 Docker 與 Node.js。PHP 與 MySQL 都在容器內執行。

```sh
cp .env.example .env
docker compose run --rm --no-deps app composer install
docker compose run --rm --no-deps app php artisan key:generate
npm ci
npm run build
docker compose run --rm app php artisan migrate --seed
docker compose up -d
```

網站位於 <http://localhost:8000>。

## 測試

Feature Test 使用 MySQL 的 `bairi_testing` 資料庫，並會讀取 `npm run build` 產生的資源。

```sh
docker compose run --rm app ./vendor/bin/pest
```
