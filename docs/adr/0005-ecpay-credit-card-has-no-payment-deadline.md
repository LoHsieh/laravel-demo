# 綠界信用卡付款無法設定付款期限，改由訂單端把關

規格要求「送往綠界的付款期限不超過訂單剩餘的付款時間」（US 79），並要求實作前查證綠界信用卡付款可設定的參數。查證結果（2026-11-13，綠界全方位金流 AioCheckOut V5 文件）：

- 信用卡（`ChoosePayment=Credit`）沒有任何設定付款期限的參數。`ExpireDate` 只用於 ATM（天）、`StoreExpireDate` 只用於超商代碼，信用卡專屬參數只有 `Redeem`、`UnionPay`、`BindingCard`、`MerchantMemberID`。（[產生訂單](https://developers.ecpay.com.tw/?p=2862)、[信用卡一次付清](https://developers.ecpay.com.tw/?p=2866)）
- `MerchantTradeDate` 只規定格式 `yyyy/MM/dd HH:mm:ss`，文件沒有說明它的有效時間，也沒有說明付款頁多久後失效。（[介接注意事項](https://developers.ecpay.com.tw/?p=2858)、[常見技術 FAQ](https://developers.ecpay.com.tw/?p=61944)）
- 特店交易編號（`MerchantTradeNo`）「均為唯一值，不可重複使用」，最長 20 碼英數。

因此無法讓綠界的付款頁在訂單付款期限到期時失效。我們改在訂單端把關：

- 只有待付款且未超過付款期限的訂單才能開始付款（`StartPayment`），逾期後不會再導向綠界。
- 付款通知處理時再檢查一次付款期限，以綠界回傳的付款時間（`PaymentDate`）比對，而不是通知送達的時間：綠界會在 5～15 分鐘後重送通知，期限前付款的不該因重送而失效。逾期後才付款的付款成功只記錄付款，訂單不會變成已付款（變成付款異常由 #12 處理）。
- 付款模組的 `PaymentRequest` 仍帶著付款期限，將來換成支援付款期限的金流時直接送出。

## Consequences

- 顧客在付款期限前一刻進入綠界付款頁，仍可能在逾期後才完成付款，這時款項已扣但訂單不成立，需要店家人工處理（付款異常）。
- 每次付款嘗試（包含付款失敗後重新付款）都建立一筆付款，各自有新的交易編號：訂單編號 14 碼 + 6 碼隨機英數。付款通知以綠界的交易編號（`TradeNo`）去重。
- 付款結果以付款通知（ReturnURL）為準。顧客付款後被導回的 OrderResultURL 雖然也帶檢查碼，只用來找出訂單並顯示結果頁，不改變訂單。本機開發時綠界無法連到 ReturnURL，結果頁會停在「付款結果確認中」。
