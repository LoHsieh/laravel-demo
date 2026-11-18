// 從 R2 讀出咖啡豆圖片給瀏覽器（ADR 0008）。部署在 Workers 免費方案，每天超過 10 萬次請求就回錯誤而不計費，
// 因此 R2 的讀取次數不會超出免費額度。上傳不經過這裡，由 Laravel 以 S3 API 直接寫入 R2。
export default {
    async fetch(request, env) {
        if (request.method !== 'GET' && request.method !== 'HEAD') {
            return new Response('Method Not Allowed', { status: 405, headers: { allow: 'GET, HEAD' } });
        }

        // 上傳的檔名是英數亂數，不需要解碼網址。
        const key = new URL(request.url).pathname.slice(1);
        // 只公開咖啡豆圖片，bucket 裡其他路徑的檔案不從這裡讀出。
        if (!key.startsWith('products/')) {
            return notFound();
        }

        // 交給 R2 比對 If-None-Match：瀏覽器已有相同版本時，R2 回傳沒有 body 的物件。
        const object = await env.BUCKET.get(key, { onlyIf: request.headers });
        if (object === null) {
            return notFound();
        }

        const headers = new Headers();
        object.writeHttpMetadata(headers);
        headers.set('etag', object.httpEtag);
        // 檔名是上傳時產生的亂數，內容不會變，瀏覽器可以長期快取，減少回到 Worker 的請求。
        headers.set('cache-control', 'public, max-age=31536000, immutable');

        if (!('body' in object)) {
            return new Response(null, { status: 304, headers });
        }

        return new Response(object.body, { headers });
    },
};

function notFound() {
    return new Response('Not Found', { status: 404 });
}
