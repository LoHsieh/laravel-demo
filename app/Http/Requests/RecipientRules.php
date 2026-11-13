<?php

namespace App\Http\Requests;

/**
 * 收件資訊的驗證規則，會員中心的預設收件資訊與結帳頁共用。
 */
final class RecipientRules
{
    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:50'],
            // 手機或市話，允許 +、括號、空白與連字號
            'recipient_phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9()\s-]{8,20}$/'],
            'recipient_address' => ['required', 'string', 'max:255'],
        ];
    }
}
