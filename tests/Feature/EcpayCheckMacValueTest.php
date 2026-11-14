<?php

use App\Payment\Ecpay\CheckMacValue;

it('computes the CheckMacValue from the worked example in the 綠界 docs', function () {
    // https://developers.ecpay.com.tw/?p=2902 的範例：測試特店的 HashKey / HashIV 與文件上的參數
    $checkMac = new CheckMacValue('pwFHCqoQZGmho4w6', 'EkRm7iFT261dpevs');

    expect($checkMac->for([
        'TradeDesc' => '促銷方案',
        'PaymentType' => 'aio',
        'MerchantTradeDate' => '2023/03/12 15:30:23',
        'MerchantTradeNo' => 'ecpay20230312153023',
        'MerchantID' => '3002607',
        'ReturnURL' => 'https://www.ecpay.com.tw/receive.php',
        'ItemName' => 'Apple iphone 15',
        'TotalAmount' => '30000',
        'ChoosePayment' => 'ALL',
        'EncryptType' => '1',
    ]))->toBe('6C51C9E6888DE861FD62FB1DD17029FC742634498FD813DC43D4243B5685B840');
});

it('verifies a payload by its CheckMacValue field', function () {
    $checkMac = new CheckMacValue('pwFHCqoQZGmho4w6', 'EkRm7iFT261dpevs');
    $payload = ['MerchantTradeNo' => 'BR261113K7Q2XMAB12CD', 'RtnCode' => '1', 'RtnMsg' => '交易成功'];
    $payload['CheckMacValue'] = $checkMac->for($payload);

    expect($checkMac->verify($payload))->toBeTrue()
        ->and($checkMac->verify([...$payload, 'RtnCode' => '0']))->toBeFalse()
        ->and($checkMac->verify(array_diff_key($payload, ['CheckMacValue' => true])))->toBeFalse();
});
