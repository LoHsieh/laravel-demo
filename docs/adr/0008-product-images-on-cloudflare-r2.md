# 咖啡豆圖片存放在 Cloudflare R2

Render 的容器檔案系統不會保留（ADR 0002），店家上傳的咖啡豆圖片必須存在外部服務，而且這份 demo 要能長期免費上線。圖片透過 Laravel Storage 存取：咖啡豆的 `image` 欄位存放檔案在「咖啡豆圖片 disk」上的路徑，這個 disk 由 `PRODUCT_IMAGES_DISK` 設定。本機開發使用 `public` disk（本機磁碟）；線上使用 `s3` disk 指向 Cloudflare R2。切換環境只需調整環境變數，不需修改程式碼。

我們選擇 R2 而不是規格原本的候選 Cloudinary，因為 R2 與 S3 相容，可以直接使用 Laravel 內建的 `s3` driver 與官方的 `league/flysystem-aws-s3-v3`。Cloudinary 的官方 Laravel 套件（`cloudinary-labs/cloudinary-laravel` 3.0.2）只支援 Laravel 11、12，本專案是 Laravel 13，只能改用社群 fork。

2026-11-18 查證的免費額度：

- **Cloudflare R2**：每月 10 GB-month 儲存、100 萬次寫入類操作（Class A）、1,000 萬次讀取類操作（Class B），對外傳輸（egress）免費。第一次啟用 R2 時可能需要綁定付款方式，額度內不收費。
- **Cloudinary**：每月 25 credits，儲存、流量與轉換共用（1 credit 約等於 1 GB 儲存、1 GB 流量或 1,000 次轉換），以滾動 30 天計算，免費方案用完沒有加購選項。
- **Backblaze B2**：前 10 GB 免費、官方說明不需信用卡，下載流量在平均儲存量的 3 倍內免費。與 R2 一樣 S3 相容，程式不需修改即可改用。

## Considered Options

- **Cloudinary**：有圖片轉換與 CDN，但需要依賴社群 fork 才能用在 Laravel 13，額度由儲存、流量、轉換共用。
- **Backblaze B2**：同樣 S3 相容且不需信用卡；R2 的對外傳輸完全免費，且有全球 CDN，作為首選。
- **Supabase Storage**：S3 相容，但免費專案約 7 天沒有活動就暫停（見 ADR 0004）。

## Consequences

- 上傳的圖片與種子資料一樣應是牛皮紙袋插圖（設計規範不使用實拍照片），它取代 CSS 繪製的紙袋並填滿圖框；沒有圖片時才顯示 CSS 紙袋。系統不檢查圖片內容，這是給店家的規範。
- 換圖時若新檔已上傳但資料庫更新失敗，新檔會留在 bucket 中；刪除舊檔失敗（`throw` 為 false）也不會報錯。兩者都只會多留檔案，不影響顯示。
- 本機需先執行 `php artisan storage:link`，`public` disk 的圖片才能從 `/storage` 網址讀取。
- R2 不支援物件層級的 ACL，圖片是否公開由 bucket 的公開網址（r2.dev 或自訂網域）決定。`s3` disk 不設定 `visibility`，讓上傳維持預設的 `private` ACL，R2 會接受並忽略它；改成 `public` 會送出 `public-read`，曾有使用者回報 R2 因此拒絕上傳。
- 公開的店家測試帳號任何人都能上傳，因此只接受 2 MB 以內的 JPEG、PNG、WebP（不接受可夾帶程式碼的 SVG），避免圖片服務被當成任意檔案空間。
- 換圖或移除圖片時，在資料庫更新成功後才刪除舊檔。demo 資料重置（ADR 0003）只重建資料庫，不會清除透過店家測試帳號上傳到 R2 的圖片，這些檔案會留在 bucket 中；以 2 MB 上限計算，要約 5,000 次上傳才會用完 10 GB。若成為問題，再加上定期清理。
- 種子資料目前沒有圖片，前台顯示牛皮紙袋插圖。日後替種子資料加上圖片時，把檔案放在 R2 的固定路徑並寫入該路徑，與上傳的圖片一樣透過 disk 產生網址（ADR 0003 所說的「只寫入網址，不重新上傳」）。
- R2 的端點、金鑰與公開網址只能在線上驗證：部署後要實際上傳一次圖片，並在容器重啟後確認圖片仍然存在。
