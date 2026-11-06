<?php

// 只收錄前台表單用到的規則，其餘沿用 Laravel 內建的英文訊息。
return [
    'confirmed' => ':attribute與確認欄位不一致。',
    'email' => ':attribute格式不正確。',
    'lowercase' => ':attribute必須為小寫。',
    'max' => [
        'string' => ':attribute不可超過 :max 個字元。',
    ],
    'min' => [
        'string' => ':attribute至少需要 :min 個字元。',
    ],
    'regex' => ':attribute格式不正確。',
    'required' => '請填寫:attribute。',
    'string' => ':attribute必須為文字。',
    'unique' => '這個:attribute已經註冊過了。',

    'password' => [
        'min' => ':attribute至少需要 :min 個字元。',
    ],

    'attributes' => [
        'email' => 'Email',
        'password' => '密碼',
        'recipient_name' => '收件人姓名',
        'recipient_phone' => '收件人電話',
        'recipient_address' => '收件地址',
    ],
];
