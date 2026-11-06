/**
 * 購物車的加入、修改、移除請求。成功時回傳更新後的購物車，並廣播 cart:updated 讓頁首數量跟著更新；
 * 失敗時丟出帶有可直接顯示給訪客的訊息的錯誤。
 */
export async function cartRequest(method, url, body) {
    let response;

    try {
        response = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: body === undefined ? undefined : JSON.stringify(body),
        });
    } catch {
        throw new Error('連線失敗，請稍後再試。');
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(errorMessage(response.status, data));
    }

    window.dispatchEvent(new CustomEvent('cart:updated', { detail: data }));

    return data;
}

const errorMessage = (status, data) => {
    if (status === 422) return Object.values(data.errors ?? {})[0]?.[0] ?? '數量不正確，請重新選擇。';
    if (status === 404) return '找不到這個商品，請重新整理頁面。';
    if (status === 419) return '頁面已過期，請重新整理後再試。';

    return '發生錯誤，請稍後再試。';
};
