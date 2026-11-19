import assert from 'node:assert/strict';
import { test } from 'node:test';
import worker from '../src/index.js';

/**
 * 記憶體中的 R2 bucket 替身，只實作 Worker 用到的 get()。
 * 帶有相符的 If-None-Match 時，與 R2 一樣回傳沒有 body 的物件。
 */
function fakeBucket(objects) {
    return {
        async get(key, options = {}) {
            const object = objects[key];
            if (object === undefined) {
                return null;
            }

            const httpEtag = `"${object.etag}"`;
            const metadata = {
                httpEtag,
                writeHttpMetadata(headers) {
                    headers.set('content-type', object.contentType);
                },
            };

            if (options.onlyIf?.get('if-none-match') === httpEtag) {
                return metadata;
            }

            return { ...metadata, body: object.body };
        },
    };
}

const env = {
    BUCKET: fakeBucket({
        'products/abc.jpg': { body: 'jpeg-bytes', contentType: 'image/jpeg', etag: 'e1' },
        'secret/notes.txt': { body: 'secret', contentType: 'text/plain', etag: 'e2' },
    }),
};

const fetchImage = (path, init) => worker.fetch(new Request(`https://images.example.workers.dev${path}`, init), env);

test('serves a 咖啡豆 image with its content type and a long-lived cache header', async () => {
    const response = await fetchImage('/products/abc.jpg');

    assert.equal(response.status, 200);
    assert.equal(await response.text(), 'jpeg-bytes');
    assert.equal(response.headers.get('content-type'), 'image/jpeg');
    assert.equal(response.headers.get('etag'), '"e1"');
    assert.equal(response.headers.get('cache-control'), 'public, max-age=31536000, immutable');
});

test('returns 404 for a missing image and for anything outside products/', async () => {
    assert.equal((await fetchImage('/products/missing.jpg')).status, 404);
    assert.equal((await fetchImage('/secret/notes.txt')).status, 404);
    assert.equal((await fetchImage('/')).status, 404);
});

test('only answers GET and HEAD', async () => {
    const response = await fetchImage('/products/abc.jpg', { method: 'PUT', body: 'overwrite' });

    assert.equal(response.status, 405);
    assert.equal(response.headers.get('allow'), 'GET, HEAD');
    assert.equal((await fetchImage('/products/abc.jpg', { method: 'HEAD' })).status, 200);
});

test('answers 304 Not Modified when the browser already has the image', async () => {
    const response = await fetchImage('/products/abc.jpg', { headers: { 'if-none-match': '"e1"' } });

    assert.equal(response.status, 304);
    assert.equal(response.headers.get('etag'), '"e1"');
    assert.equal(await response.text(), '');
});
