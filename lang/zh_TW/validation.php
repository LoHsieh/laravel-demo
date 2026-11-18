<?php

// 只收錄前台與後台表單用到的規則，其餘沿用 Laravel 內建的英文訊息。
return [
    'confirmed' => ':attribute與確認欄位不一致。',
    'email' => ':attribute格式不正確。',
    'image' => ':attribute必須是圖片。',
    'max' => [
        'file' => ':attribute不可超過 :max KB。',
        'string' => ':attribute不可超過 :max 個字元。',
    ],
    'mimes' => ':attribute必須是 :values 格式。',
    'min' => [
        'string' => ':attribute至少需要 :min 個字元。',
    ],
    'regex' => ':attribute格式不正確。',
    'required' => '請填寫:attribute。',
    'string' => ':attribute必須為文字。',
    'unique' => '這個:attribute已經註冊過了。',
    'uploaded' => ':attribute上傳失敗，請確認檔案大小後再試一次。',

    'password' => [
        'min' => ':attribute至少需要 :min 個字元。',
    ],

    'attributes' => [
        'email' => 'Email',
        'password' => '密碼',
        'recipient_name' => '收件人姓名',
        'recipient_phone' => '收件人電話',
        'recipient_address' => '收件地址',
        'name' => '名稱',
        'origin' => '產地',
        'process' => '處理法',
        'roast' => '烘焙度',
        'flavor_description' => '風味描述',
        'flavor_notes.*' => '風味標籤',
        'weight_grams' => '重量',
        'price' => '價格',
        'stock' => '庫存',
        'image' => '圖片',
    ],
];
