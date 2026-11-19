# 咖啡豆圖片 Worker

把 Cloudflare R2 bucket 裡的咖啡豆圖片（`products/` 底下）提供給瀏覽器。Laravel 上傳時以 S3 API 直接寫入 R2，
前台的圖片網址則指向這個 Worker（ADR 0008）。

Worker 部署在 Workers 免費方案：每天超過 10 萬次請求時回傳錯誤（Error 1027），不會計費，到 UTC 午夜（台灣時間早上 8 點）
恢復。因此 R2 每月的讀取次數最多約 300 萬次，在 1,000 萬次的免費額度內。

## 測試

不需要安裝套件，以 Node 內建的 test runner 執行，R2 由記憶體中的替身取代：

```sh
npm test
```

## 第一次部署

1. 在 Cloudflare 後台的 R2 建立名為 `bairi-product-images` 的 bucket（名稱需與 `wrangler.jsonc` 一致）。不需要開啟公開網址（r2.dev）。
2. 在 R2 建立 API Token：權限選 Object Read & Write，只套用到這個 bucket。記下 Access Key ID、Secret Access Key 與 S3 端點
   （`https://<ACCOUNT_ID>.r2.cloudflarestorage.com`）。
3. 部署 Worker（第一次會開啟瀏覽器登入 Cloudflare）：

   ```sh
   npx --yes wrangler@4.164.0 login
   npm run deploy
   ```

   部署完成後會顯示 Worker 的網址：`https://bairi-product-images.<子網域>.workers.dev`。
4. 在 Render 填入 `render.yaml` 中標示 `sync: false` 的變數：`AWS_BUCKET`（`bairi-product-images`）、`AWS_ENDPOINT`、
   `AWS_ACCESS_KEY_ID`、`AWS_SECRET_ACCESS_KEY`，以及 `AWS_URL`（上一步的 Worker 網址）。
5. 到線上後台上傳一張圖片，確認前台顯示；再於 Render 重新啟動服務，確認圖片仍然存在。

修改 `src/index.js` 後，以 `npm run deploy` 重新部署；Worker 不會隨 Render 自動部署。
