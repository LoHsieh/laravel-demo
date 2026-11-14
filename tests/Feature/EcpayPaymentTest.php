<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Variant;
use App\Ordering\OrderStatus;
use App\Payment\Ecpay\CheckMacValue;
use Illuminate\Testing\TestResponse;

const ECPAY_STAGE_CHECKOUT = 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5';

beforeEach(function () {
    $this->travelTo(now()->setDateTime(2026, 11, 13, 14, 0));
    $this->customer = Customer::factory()->create();
    $this->actingAs($this->customer, 'customer');
    // 綠界測試特店（https://developers.ecpay.com.tw/?p=2856）
    $this->checkMac = new CheckMacValue('pwFHCqoQZGmho4w6', 'EkRm7iFT261dpevs');
});

/**
 * 一筆剛成立的待付款訂單：耶加雪菲 250g × 2，運費 100，總計 940。
 */
function pendingOrder(?Customer $customer = null): Order
{
    $variant = Variant::factory()
        ->for(Product::factory()->create(['name' => '耶加雪菲 孔加 水洗']))
        ->create(['weight_grams' => 250, 'price' => 420]);

    $order = Order::factory()->for($customer ?? test()->customer)->create([
        'subtotal' => 840, 'shipping_fee' => 100, 'total' => 940,
    ]);
    $order->items()->create(['variant_id' => $variant->id, 'grind' => 'pour_over', 'unit_price' => 420, 'quantity' => 2]);

    return $order;
}

/**
 * 從開始付款的回應中取出要送往綠界的表單。
 *
 * @return array{action: string, fields: array<string, string>}
 */
function ecpayForm(TestResponse $response): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$response->getContent());
    $form = (new DOMXPath($dom))->query('//form[@id="ecpay-checkout"]')->item(0);
    expect($form)->not->toBeNull();

    $fields = [];
    foreach ($form->getElementsByTagName('input') as $input) {
        $fields[$input->getAttribute('name')] = $input->getAttribute('value');
    }

    return ['action' => $form->getAttribute('action'), 'fields' => $fields];
}

function startPayment(Order $order): TestResponse
{
    return test()->post(route('payments.store', $order));
}

it('sends the 顧客 to the 綠界 credit card payment page with the order total and a valid 檢查碼', function () {
    $order = pendingOrder();

    $form = ecpayForm(startPayment($order)->assertOk());

    expect($form['action'])->toBe(ECPAY_STAGE_CHECKOUT)
        ->and($form['fields'])->toMatchArray([
            'MerchantID' => '3002607',
            'PaymentType' => 'aio',
            'ChoosePayment' => 'Credit',
            'EncryptType' => '1',
            'TotalAmount' => '940',
            'MerchantTradeDate' => '2026/11/13 22:00:00',
            'ItemName' => '耶加雪菲 孔加 水洗 250g x 2',
            'ReturnURL' => url('/payment/ecpay/notify'),
            'OrderResultURL' => url('/payment/ecpay/return'),
        ])
        ->and($form['fields']['MerchantTradeNo'])->toStartWith($order->number)->toHaveLength(20)
        ->and($this->checkMac->verify($form['fields']))->toBeTrue();
});

it('uses a new 綠界 MerchantTradeNo for every payment attempt', function () {
    $order = pendingOrder();

    $first = ecpayForm(startPayment($order))['fields']['MerchantTradeNo'];
    $second = ecpayForm(startPayment($order))['fields']['MerchantTradeNo'];

    expect($first)->not->toBe($second);
});

it('does not start a payment once the 付款期限 has passed', function () {
    $order = pendingOrder();
    $this->travel(30)->minutes();

    $this->from(route('checkout.placed', $order))->post(route('payments.store', $order))
        ->assertRedirect(route('checkout.placed', $order))
        ->assertSessionHasErrors(['payment' => '已超過付款期限，無法付款。']);
});

it('does not start a payment for an 訂單 that is no longer 待付款', function () {
    $order = pendingOrder();
    $order->transitionTo(OrderStatus::Cancelled);

    $this->post(route('payments.store', $order))->assertSessionHasErrors('payment');
});

it('hides other customers’ orders from the payment routes', function () {
    $order = pendingOrder(Customer::factory()->create());

    startPayment($order)->assertNotFound();
});

/**
 * 開始付款，回傳這次付款嘗試送往綠界的 MerchantTradeNo。
 */
function paymentAttempt(Order $order): string
{
    return ecpayForm(startPayment($order))['fields']['MerchantTradeNo'];
}

/**
 * 依綠界付款結果通知的格式（https://developers.ecpay.com.tw/?p=2878）組出通知，帶上正確的檢查碼。
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function ecpayNotification(string $merchantTradeNo, array $overrides = []): array
{
    $payload = [
        'MerchantID' => '3002607',
        'MerchantTradeNo' => $merchantTradeNo,
        'StoreID' => '',
        'RtnCode' => '1',
        'RtnMsg' => '交易成功',
        'TradeNo' => '2611131405123456',
        'TradeAmt' => '940',
        'PaymentDate' => '2026/11/13 22:05:12',
        'PaymentType' => 'Credit_CreditCard',
        'PaymentTypeChargeFee' => '19',
        'TradeDate' => '2026/11/13 22:00:00',
        'SimulatePaid' => '0',
        'CustomField1' => '', 'CustomField2' => '', 'CustomField3' => '', 'CustomField4' => '',
        ...$overrides,
    ];

    return [...$payload, 'CheckMacValue' => test()->checkMac->for($payload)];
}

function notify(array $payload): TestResponse
{
    return test()->post('/payment/ecpay/notify', $payload);
}

it('marks a 待付款 訂單 as 已付款 when a correctly signed 付款成功 notification arrives', function () {
    $order = pendingOrder();
    $merchantTradeNo = paymentAttempt($order);

    notify(ecpayNotification($merchantTradeNo))->assertOk()->assertContent('1|OK');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('rejects a notification with a wrong 檢查碼 and leaves the 訂單 unchanged', function () {
    $order = pendingOrder();
    $payload = ecpayNotification(paymentAttempt($order));

    notify([...$payload, 'CheckMacValue' => str_repeat('A', 64)])->assertBadRequest();
    notify([...$payload, 'TradeAmt' => '1'])->assertBadRequest();

    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('processes the same notification only once when it is delivered again', function () {
    $order = pendingOrder();
    $payload = ecpayNotification(paymentAttempt($order));

    notify($payload)->assertContent('1|OK');
    notify($payload)->assertOk()->assertContent('1|OK');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('rejects a correctly signed notification whose amount differs from the payment', function () {
    $order = pendingOrder();

    notify(ecpayNotification(paymentAttempt($order), ['TradeAmt' => '1']))->assertBadRequest();

    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('keeps the 訂單 待付款 after a failed payment so the 顧客 can pay again within the 付款期限', function () {
    $order = pendingOrder();

    notify(ecpayNotification(paymentAttempt($order), ['RtnCode' => '10100248', 'RtnMsg' => '拒絕交易']))
        ->assertContent('1|OK');
    expect($order->fresh()->status)->toBe(OrderStatus::PendingPayment);

    $retry = paymentAttempt($order);
    notify(ecpayNotification($retry, ['TradeNo' => '2611131410654321']))->assertContent('1|OK');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('does not mark the 訂單 as 已付款 when the 顧客 paid after the 付款期限', function () {
    $order = pendingOrder();
    $merchantTradeNo = paymentAttempt($order);
    $this->travel(31)->minutes();

    // 付款期限是台北時間 22:30
    notify(ecpayNotification($merchantTradeNo, ['PaymentDate' => '2026/11/13 22:31:00']))->assertContent('1|OK');

    expect($order->fresh()->status)->not->toBe(OrderStatus::Paid);
});

it('marks the 訂單 as 已付款 when the 顧客 paid in time but the notification was redelivered after the 付款期限', function () {
    $order = pendingOrder();
    $merchantTradeNo = paymentAttempt($order);
    // 綠界在 5～15 分鐘後重送通知
    $this->travel(40)->minutes();

    notify(ecpayNotification($merchantTradeNo, ['PaymentDate' => '2026/11/13 22:25:00']))->assertContent('1|OK');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('brings the 顧客 back to the payment result page after paying on 綠界, without touching their session', function () {
    $order = pendingOrder();
    $payload = ecpayNotification(paymentAttempt($order));

    // 綠界跨站 POST 回來時瀏覽器不帶 session cookie；若這裡發新的 session cookie，會蓋掉顧客的登入狀態
    $this->post('/payment/ecpay/return', $payload)
        ->assertRedirect(route('payments.show', $order))
        ->assertCookieMissing(config('session.cookie'));
});

it('ignores a return to the site with a wrong 檢查碼', function () {
    $order = pendingOrder();
    $payload = ecpayNotification(paymentAttempt($order));

    $this->post('/payment/ecpay/return', [...$payload, 'CheckMacValue' => str_repeat('A', 64)])->assertNotFound();
});

it('shows a successful payment once the notification has arrived', function () {
    $order = pendingOrder();
    notify(ecpayNotification(paymentAttempt($order)));

    $this->get(route('payments.show', $order))
        ->assertOk()
        ->assertSeeText('付款成功')
        ->assertSeeText($order->number)
        ->assertDontSee(route('payments.store', $order));
});

it('shows a failed payment with a way to pay again', function () {
    $order = pendingOrder();
    notify(ecpayNotification(paymentAttempt($order), ['RtnCode' => '10100248', 'RtnMsg' => '拒絕交易']));

    $this->get(route('payments.show', $order))
        ->assertOk()
        ->assertSeeText('付款失敗')
        ->assertSeeText('拒絕交易')
        ->assertSeeText('2026/11/13 22:30')
        ->assertSee('action="'.route('payments.store', $order).'"', false);
});

it('says the result is still being confirmed when the notification has not arrived yet', function () {
    $order = pendingOrder();
    paymentAttempt($order);

    $this->get(route('payments.show', $order))
        ->assertOk()
        ->assertSeeText('付款結果確認中')
        // 顧客可能只是關掉了綠界的付款頁，綠界不會送通知；不能讓顧客卡在這裡
        ->assertSee('action="'.route('payments.store', $order).'"', false);
});

it('hides other customers’ payment results', function () {
    $order = pendingOrder(Customer::factory()->create());

    $this->get(route('payments.show', $order))->assertNotFound();
});

it('offers to pay right after the 訂單 is placed', function () {
    $order = pendingOrder();

    $this->get(route('checkout.placed', $order))
        ->assertOk()
        ->assertSee('action="'.route('payments.store', $order).'"', false)
        ->assertSeeText('前往綠界付款');
});
