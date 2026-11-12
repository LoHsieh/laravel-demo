<!doctype html>
<html lang="zh-Hant">
<body style="margin:0;background:#f5f1ea;color:#26221d;font-family:'Noto Sans TC',sans-serif;font-size:15px;line-height:1.8;">
    <div style="max-width:520px;margin:0 auto;padding:48px 24px;">
        <p style="font-family:'Noto Serif TC',serif;font-size:22px;font-weight:600;letter-spacing:0.14em;margin:0 0 32px;">焙日 BAIRI</p>
        <p>你好：</p>
        <p>我們收到重設焙日帳號密碼的申請。請點下方按鈕設定新密碼，連結將在 {{ config('auth.passwords.customers.expire') }} 分鐘後失效。</p>
        <p style="margin:32px 0;">
            <a href="{{ $url }}" style="display:inline-block;background:#26221d;color:#f5f1ea;padding:14px 32px;text-decoration:none;letter-spacing:0.14em;">重設密碼</a>
        </p>
        <p style="color:#5f574d;font-size:13px;">如果按鈕無法點選，請複製這個網址到瀏覽器開啟：<br>{{ $url }}</p>
        <p style="color:#5f574d;font-size:13px;">如果你沒有申請重設密碼，可以忽略這封信，密碼不會改變。</p>
    </div>
</body>
</html>
