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

it('turns an overdue 訂單 the scheduler has not cancelled yet into 付款異常 when the 顧客 paid after the 付款期限', function () {
    $order = pendingOrder();
    $variant = $order->items()->first()->variant;
    $merchantTradeNo = paymentAttempt($order);
    $this->travel(31)->minutes();

    // 付款期限是台北時間 22:30
    notify(ecpayNotification($merchantTradeNo, ['PaymentDate' => '2026/11/13 22:31:00']))->assertContent('1|OK');

    // 先照付款逾期處理（歸還庫存保留、放回購物車），再因收到付款變成付款異常
    expect($order->fresh()->status)->toBe(OrderStatus::PaymentException)
        ->and($variant->fresh()->stock)->toBe($variant->stock + 2)
        ->and($this->getJson('/cart')->json('count'))->toBe(2);
});

it('turns a 已取消 訂單 into 付款異常 when a 付款成功 notification arrives afterwards', function () {
    $order = pendingOrder();
    $merchantTradeNo = paymentAttempt($order);
    $this->post(route('account.orders.cancel', $order));

    notify(ecpayNotification($merchantTradeNo))->assertContent('1|OK');

    expect($order->fresh()->status)->toBe(OrderStatus::PaymentException);
});

it('turns a 訂單 cancelled for 付款逾期 into 付款異常 when a 付款成功 notification arrives afterwards', function () {
    $order = pendingOrder();
    $merchantTradeNo = paymentAttempt($order);
    $this->travel(31)->minutes();
    $this->artisan('schedule:run');

    notify(ecpayNotification($merchantTradeNo, ['PaymentDate' => '2026/11/13 22:31:00']))->assertContent('1|OK');

    expect($order->fresh()->status)->toBe(OrderStatus::PaymentException)
        ->and($this->getJson('/cart')->json('count'))->toBe(2);

    $this->get(route('account.orders.show', $order))
        ->assertSeeText('付款異常')
        ->assertDontSeeText('付款逾期，訂單已取消，訂單項目已放回購物車。');
});

it('keeps a 已取消 訂單 已取消 when a 付款失敗 notification arrives afterwards', function () {
    $order = pendingOrder();
    $merchantTradeNo = paymentAttempt($order);
    $this->post(route('account.orders.cancel', $order));

    notify(ecpayNotification($merchantTradeNo, ['RtnCode' => '10100248', 'RtnMsg' => '拒絕交易']))->assertContent('1|OK');

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
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

describe('telling the 顧客 about a 付款異常', function () {
    /**
     * 顧客在付款期限後才於綠界付款成功，訂單因付款逾期取消後變成付款異常。
     */
    function paymentExceptionAfterExpiry(): Order
    {
        $order = pendingOrder();
        $merchantTradeNo = paymentAttempt($order);
        test()->travel(31)->minutes();
        notify(ecpayNotification($merchantTradeNo, ['PaymentDate' => '2026/11/13 22:31:00']))->assertContent('1|OK');

        return $order;
    }

    /**
     * 顧客自行取消訂單後，才完成先前開始的綠界付款，訂單變成付款異常。
     */
    function paymentExceptionAfterCancel(): Order
    {
        $order = pendingOrder();
        $merchantTradeNo = paymentAttempt($order);
        test()->post(route('account.orders.cancel', $order));
        notify(ecpayNotification($merchantTradeNo))->assertContent('1|OK');

        return $order;
    }

    dataset('pages showing the 訂單', [
        '訂單詳情' => 'account.orders.show',
        '付款結果' => 'payments.show',
    ]);

    it('says the payment was received, the 訂單 will not ship and the money will be refunded', function (string $page) {
        $order = paymentExceptionAfterCancel();

        $this->get(route($page, $order))
            ->assertOk()
            ->assertSeeText('付款異常')
            ->assertSeeText('我們已收到你的付款，但這筆訂單已取消，不會出貨。店家會與你聯絡，將款項退回。')
            ->assertDontSeeText('付款成功')
            ->assertDontSeeText('放回購物車')
            ->assertDontSee(route('payments.store', $order), false);
    })->with('pages showing the 訂單');

    it('reminds the 顧客 that the 訂單項目 are back in the 購物車 and a new 訂單 is a separate payment after 付款逾期', function (string $page) {
        $order = paymentExceptionAfterExpiry();

        $this->get(route($page, $order))
            ->assertOk()
            ->assertSeeText('我們已收到你的付款，但這筆訂單已取消，不會出貨。店家會與你聯絡，將款項退回。')
            ->assertSeeText('訂單項目已在付款逾期時放回購物車。重新下單會是一筆新的訂單，需要另外付款；這筆付款仍會退回給你。')
            ->assertDontSeeText('付款成功')
            ->assertDontSee(route('payments.store', $order), false);
    })->with('pages showing the 訂單');
});
