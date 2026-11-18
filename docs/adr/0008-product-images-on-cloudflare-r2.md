# 咖啡豆圖片存放在 Cloudflare R2，經由 Worker 讀取

Render 的容器檔案系統不會保留（ADR 0002），店家上傳的咖啡豆圖片必須存在外部服務，而且這份 demo 要能長期免費上線、不產生額外費用。圖片透過 Laravel Storage 存取：咖啡豆的 `image` 欄位存放檔案在「咖啡豆圖片 disk」上的路徑，這個 disk 由 `PRODUCT_IMAGES_DISK` 設定。本機開發使用 `public` disk（本機磁碟）；線上使用 `s3` disk 指向 Cloudflare R2。切換環境只需調整環境變數，不需修改程式碼。

我們選擇 R2 而不是規格原本的候選 Cloudinary，因為 R2 與 S3 相容，可以直接使用 Laravel 內建的 `s3` driver 與官方的 `league/flysystem-aws-s3-v3`。Cloudinary 的官方 Laravel 套件（`cloudinary-labs/cloudinary-laravel` 3.0.2）只支援 Laravel 11、12，本專案是 Laravel 13，只能改用社群 fork。

R2 沒有可以設定的花費上限（Cloudflare 的 Spend limits 只涵蓋 Workers、Durable Objects 與 AI Gateway，預算警示只寄信不阻擋），因此由我們自己讓用量停在免費額度內：

- **讀取**：圖片網址不指向 R2 的公開網址，而是指向 `workers/product-images` 的 Worker，由它從 R2 讀出圖片。Worker 部署在 Workers 免費方案，每天超過 10 萬次請求就回錯誤而不計費，所以 R2 每月最多約 300 萬次讀取，在 1,000 萬次的免費額度內。
- **寫入與儲存**：店家新增、編輯咖啡豆以 IP 限制每分鐘 10 次；全站 24 小時內最多接受 `PRODUCT_IMAGES_DAILY_UPLOADS`（200）張圖片，每月最多約 6,000 次上傳，在 100 萬次寫入的免費額度內。

2026-11-18 查證的免費額度：

- **Cloudflare R2**：每月 10 GB-month 儲存、100 萬次寫入類操作（Class A）、1,000 萬次讀取類操作（Class B），對外傳輸（egress）免費。超出後儲存 $0.015/GB-month、寫入 $4.50/百萬次、讀取 $0.36/百萬次。第一次啟用 R2 時可能需要綁定付款方式。
- **Cloudinary**：每月 25 credits，儲存、流量與轉換共用（1 credit 約等於 1 GB 儲存、1 GB 流量或 1,000 次轉換），以滾動 30 天計算，免費方案用完沒有加購選項。
- **Backblaze B2**：前 10 GB 免費、官方說明不需信用卡，下載流量在平均儲存量的 3 倍內免費。可以設定每日花費上限（caps）。

## Considered Options

- **Cloudinary**：有圖片轉換與 CDN，但需要依賴社群 fork 才能用在 Laravel 13，額度由儲存、流量、轉換共用。
- **Backblaze B2**：同樣 S3 相容，可設每日花費上限；R2 的對外傳輸完全免費，搭配 Worker 也能把讀取壓在免費額度內，作為首選。改用 B2 只需調整設定。
- **R2 的 r2.dev 公開網址**：不用寫 Worker，但官方說明不適合正式環境，速率限制不固定（約每秒數百次），無法把讀取次數壓在免費額度內。
- **R2 綁自訂網域並以 CDN 快取**：付費用戶的標準做法，但需要一個放在 Cloudflare 上的網域，本專案沒有。
- **Supabase Storage**：S3 相容，但免費專案約 7 天沒有活動就暫停（見 ADR 0004）。

## Consequences

- Worker 不會隨 Render 自動部署，修改後需以 `npm run deploy` 手動部署；設定步驟見 `workers/product-images/README.md`。
- 一天的請求超過 10 萬次後，當天剩下的時間圖片載入失敗，到 UTC 午夜恢復。前台沒有圖片時的紙袋插圖不受影響，但已上傳圖片的咖啡豆會顯示破圖。
- workers.dev 上的 Worker 無法使用 Cloudflare 的邊緣快取，因此 Worker 回傳長期快取標頭並支援 ETag，讓同一位訪客重複瀏覽時由瀏覽器快取回應。
- Worker 只提供 `products/` 底下的檔案，bucket 的 S3 API 金鑰只存在 Render。bucket 本身不開啟公開網址。
- 每日上傳上限是全站共用的：達到上限後，所有人當天都只能不附圖片儲存咖啡豆。
- 上傳的圖片與種子資料一樣應是牛皮紙袋插圖（設計規範不使用實拍照片），它取代 CSS 繪製的紙袋並填滿圖框；沒有圖片時才顯示 CSS 紙袋。系統不檢查圖片內容，這是給店家的規範。
- 只接受 2 MB 以內的 JPEG、PNG、WebP（不接受可夾帶程式碼的 SVG），避免圖片服務被當成任意檔案空間。
- 換圖或移除圖片時，在資料庫更新成功後才刪除舊檔。若新檔已上傳但資料庫更新失敗，新檔會留在 bucket 中；刪除舊檔失敗（`throw` 為 false）也不會報錯。兩者都只會多留檔案，不影響顯示。
- demo 資料重置（ADR 0003，#18）重建資料庫時，應一併清除透過店家測試帳號上傳的圖片，否則儲存量只增不減；在那之前，每日上傳上限讓儲存量每天最多增加約 400 MB。
- 種子資料目前沒有圖片，前台顯示牛皮紙袋插圖。日後替種子資料加上圖片時，把檔案放在 R2 的固定路徑並寫入該路徑，與上傳的圖片一樣透過 disk 產生網址（ADR 0003 所說的「只寫入網址，不重新上傳」）；路徑需在 Worker 提供的 `products/` 底下，且不被重置時的清除刪掉。
- 本機需先執行 `php artisan storage:link`，`public` disk 的圖片才能從 `/storage` 網址讀取。
- R2 不支援物件層級的 ACL。`s3` disk 不設定 `visibility`，讓上傳維持預設的 `private` ACL，R2 會接受並忽略它；改成 `public` 會送出 `public-read`，曾有使用者回報 R2 因此拒絕上傳。
- R2 的端點、金鑰與 Worker 只能在線上驗證：部署後要實際上傳一次圖片，並在容器重啟後確認圖片仍然存在。
