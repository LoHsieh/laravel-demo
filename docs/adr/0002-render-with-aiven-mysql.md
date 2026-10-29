# 網站部署在 Render，資料庫使用 Aiven MySQL

這是一份求職用的 demo，必須能長期免費上線。網站以 Docker 部署在 Render 的免費方案（Render 沒有原生 PHP 環境）；資料庫不用 Render 的免費 Postgres，因為它在建立 30 天後就會失效，而是使用 Aiven 的免費 MySQL，沒有時間限制，也符合原本選定的 MySQL 技術棧。

## Considered Options

- **Render 免費 Postgres**：每 30 天需重建或付費，且需放棄 MySQL。
- **Railway / Fly.io / Laravel Cloud**：都沒有足以長期運行 Laravel 加資料庫的免費額度。

## Consequences

- Render 的檔案系統不會保留，商品圖片必須存放在外部服務。
- 免費網站閒置 15 分鐘後休眠，首次載入約需 1 分鐘，於 README 註明而不另做保持喚醒的機制。
- 免費方案不提供背景工作程序（worker）與排程（cron job），因此網站、Laravel 排程（`schedule:work`）與 Queue（`queue:work`）由 supervisor 在同一個容器內一起執行。休眠期間排程停止，醒來後才處理逾期事項；收到付款通知與顧客查看訂單時會再檢查一次付款期限。
- 網站與資料庫位於不同服務商，每次查詢多一段網路延遲；對 demo 的流量可以接受。
