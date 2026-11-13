<?php

namespace App\Payment\Ecpay;

/**
 * 綠界的檢查碼（CheckMacValue，SHA256）。送往綠界的參數與綠界回傳的通知都以它驗證未被竄改。
 *
 * 演算法依 https://developers.ecpay.com.tw/?p=2902：參數依名稱排序、前後接上 HashKey 與 HashIV、
 * 以 .NET 規則 URL encode、轉小寫、SHA256、轉大寫。
 */
final class CheckMacValue
{
    /**
     * PHP 的 urlencode() 與綠界採用的 .NET 編碼不同的字元，換回 .NET 的結果（https://developers.ecpay.com.tw/?p=2904）。
     */
    private const DOTNET_UNESCAPED = [
        '%2d' => '-', '%5f' => '_', '%2e' => '.', '%21' => '!', '%2a' => '*', '%28' => '(', '%29' => ')',
    ];

    public function __construct(
        private readonly string $hashKey,
        private readonly string $hashIv,
    ) {}

    /**
     * @param  array<string, string|int>  $params  不含 CheckMacValue 的參數；若含有會被忽略
     */
    public function for(array $params): string
    {
        unset($params['CheckMacValue']);
        uksort($params, 'strcasecmp');

        $query = collect($params)->map(fn ($value, $key) => "{$key}={$value}")->implode('&');
        $encoded = strtolower(urlencode("HashKey={$this->hashKey}&{$query}&HashIV={$this->hashIv}"));

        return strtoupper(hash('sha256', strtr($encoded, self::DOTNET_UNESCAPED)));
    }

    /**
     * @param  array<string, mixed>  $payload  含 CheckMacValue 的參數
     */
    public function verify(array $payload): bool
    {
        $given = $payload['CheckMacValue'] ?? null;

        return is_string($given) && hash_equals($this->for($payload), strtoupper($given));
    }
}
