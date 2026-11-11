# 資料庫改用 TiDB Cloud Starter

取代 ADR 0002 的資料庫部分；網站仍以 Docker 部署在 Render 免費方案，ADR 0002 其餘後果（外部圖片儲存、休眠、supervisor 同時執行排程與 Queue）不變。

Aiven 已不再提供免費 MySQL，這份 demo 仍必須能長期免費上線。我們改用 TiDB Cloud Starter：它與 MySQL 相容，本機、測試與 CI 可以繼續使用 MySQL，不必改寫程式或測試環境；免費額度（每個實例 5 GiB 儲存、每月 5,000 萬 RU）對 demo 流量足夠，也不需要信用卡。

## Considered Options

- **Supabase**：免費專案約 7 天沒有活動就暫停，需要到後台手動恢復，面試官可能遇到無法使用的網站；且必須從 MySQL 換成 Postgres。
- **Neon**：閒置後會自動喚醒，但同樣必須換成 Postgres，本機、測試、CI 與既有程式都要跟著改。
- **Render 免費 Postgres**：每 30 天失效（見 ADR 0002）。

## Consequences

- 本機、測試與 CI 使用 MySQL 8，正式環境是 TiDB，兩者的差異只會在正式環境出現，每次影響資料庫的變更都要在線上驗證。
- TiDB 不支援 `SELECT ... FOR UPDATE SKIP LOCKED`，而 Laravel 的 database queue 遇到 MySQL 8 以上就會使用它。正式環境以 `DB_SERVER_VERSION=5.7` 讓 Laravel 改用一般的 `FOR UPDATE`；只有一個 queue worker，不會有搶鎖問題。
- TiDB 可能在 30 分鐘後中斷連線，因此 queue worker 以 `--max-time` 在此之前自行重啟。
- AUTO_INCREMENT 的 ID 會跳號（一次配發一批，重啟後跳到下一批）。顯示給顧客的訂單編號與送給金流服務的交易編號不使用資料庫 ID。
- 搜尋不分大小寫依賴資料表的 `utf8mb4_unicode_ci` 排序規則與 TiDB 的新排序框架；TiDB 的 `utf8mb4` 預設排序規則是分大小寫的 `utf8mb4_bin`，建表時必須明確指定排序規則（Laravel 預設會指定）。
- 連線強制 TLS，憑證由 Let's Encrypt 簽發，使用系統內建的 CA，不再上傳 CA 憑證檔。
- 超過每月 RU 額度時，新連線會被拒絕直到下個月。
- 免費實例閒置時會縮到零；官方文件沒有說明喚醒所需時間與長期閒置的處理方式，以上線後實測為準。
