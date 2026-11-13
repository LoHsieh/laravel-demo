<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Variant;

function logIn(string $email = 'mei@example.com', string $password = 'roast-daily-2026'): void
{
    test()->post('/login', ['email' => $email, 'password' => $password]);
}

beforeEach(function () {
    $this->customer = Customer::factory()->create(['email' => 'mei@example.com', 'password' => 'roast-daily-2026']);
});

it('keeps the 顧客 購物車 after logging out and in again on another device', function () {
    $variant = Variant::factory()->create(['stock' => 10]);
    logIn();
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 2])->assertOk();

    $this->post('/logout');
    expect($this->getJson('/cart')->json('count'))->toBe(0);

    $this->flushSession();
    logIn();

    $this->getJson('/cart')
        ->assertJsonPath('count', 2)
        ->assertJsonPath('items.0.grind_label', '手沖');
});

it('merges the 訪客 購物車 into the 顧客 購物車 on login, adding up the same 規格 and 研磨方式', function () {
    $variant = Variant::factory()->create(['stock' => 10]);
    $other = Variant::factory()->create(['stock' => 10]);
    logIn();
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 2]);
    $this->post('/logout');

    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 3]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'espresso', 'quantity' => 1]);
    $this->postJson('/cart/items', ['variant_id' => $other->id, 'grind' => 'whole_bean', 'quantity' => 1]);
    logIn();

    $items = $this->getJson('/cart')->assertJsonPath('count', 7)->json('items');

    expect(array_map(fn (array $item) => [$item['key'], $item['quantity']], $items))->toBe([
        ["{$variant->id}-pour_over", 5],
        ["{$variant->id}-espresso", 1],
        ["{$other->id}-whole_bean", 1],
    ]);

    $this->post('/logout');
    expect($this->getJson('/cart')->json('count'))->toBe(0);
});

it('merges the 訪客 購物車 into the new 顧客 購物車 on registration', function () {
    $variant = Variant::factory()->create(['stock' => 10]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 2]);

    $this->post('/register', [
        'email' => 'kai@example.com',
        'password' => 'roast-daily-2026',
        'password_confirmation' => 'roast-daily-2026',
    ]);
    $this->post('/logout');
    logIn('kai@example.com');

    $this->getJson('/cart')->assertJsonPath('count', 2);
});

it('caps a merged 購物車項目 at the stock of the 規格 and tells the 顧客', function () {
    $variant = Variant::factory()
        ->for(Product::factory()->create(['name' => '耶加雪菲 孔加 水洗']))
        ->create(['weight_grams' => 250, 'stock' => 5]);
    logIn();
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 3]);
    $this->post('/logout');

    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 4]);
    $this->followingRedirects()
        ->post('/login', ['email' => 'mei@example.com', 'password' => 'roast-daily-2026'])
        ->assertSeeText('耶加雪菲 孔加 水洗 250g・手沖：庫存僅剩 5 包，合併後調整為 5 包。');

    $this->getJson('/cart')->assertJsonPath('items.0.quantity', 5);
});

it('counts every 研磨方式 of the 規格 when capping, and leaves out what no longer fits', function () {
    $variant = Variant::factory()
        ->for(Product::factory()->create(['name' => '哥倫比亞 慧蘭 水洗']))
        ->create(['weight_grams' => 100, 'stock' => 3]);
    logIn();
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'whole_bean', 'quantity' => 3]);
    $this->post('/logout');

    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'espresso', 'quantity' => 1]);
    $this->followingRedirects()
        ->post('/login', ['email' => 'mei@example.com', 'password' => 'roast-daily-2026'])
        ->assertSeeText('哥倫比亞 慧蘭 水洗 100g・義式：庫存僅剩 3 包，未併入購物車。');

    $this->getJson('/cart')
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('items.0.grind_label', '原豆')
        ->assertJsonPath('count', 3);
});

it('does not tell the 顧客 anything when the whole 訪客 購物車 fits', function () {
    $variant = Variant::factory()->create(['stock' => 5]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 5]);

    $this->followingRedirects()
        ->post('/login', ['email' => 'mei@example.com', 'password' => 'roast-daily-2026'])
        ->assertDontSeeText('合併後調整')
        ->assertDontSeeText('未併入購物車');
});

it('tells the 顧客 once on the 購物車 page when the price of a 規格 changed after it was added', function () {
    $variant = Variant::factory()->create(['price' => 580, 'stock' => 10]);
    logIn();
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 1]);
    $this->getJson('/cart')->assertJsonPath('items.0.notice', null);

    $variant->update(['price' => 620]);

    $this->getJson('/cart')->assertJsonPath('items.0.notice', '價格已由 NT$580 調整為 NT$620。');
    preg_match('/data-vue-component="CartPage"\s+data-props="([^"]*)"/', $this->get('/cart')->getContent(), $matches);
    $props = json_decode(html_entity_decode($matches[1] ?? ''), true, flags: JSON_THROW_ON_ERROR);
    expect($props['cart']['items'][0]['notice'])->toBe('價格已由 NT$580 調整為 NT$620。');

    $this->getJson('/cart')->assertJsonPath('items.0.notice', null);
});

it('keeps the price a 訪客 saw when merging, so the change is still pointed out after login', function () {
    $variant = Variant::factory()->create(['price' => 580, 'stock' => 10]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 1]);
    $variant->update(['price' => 520]);

    logIn();

    $this->getJson('/cart')->assertJsonPath('items.0.notice', '價格已由 NT$580 調整為 NT$520。');
});

it('tells the 顧客 when the stock of a 規格 dropped below what is in the 購物車', function () {
    $variant = Variant::factory()->create(['stock' => 10]);
    $soldOut = Variant::factory()->create(['stock' => 10]);
    logIn();
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 2]);
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'espresso', 'quantity' => 2]);
    $this->postJson('/cart/items', ['variant_id' => $soldOut->id, 'grind' => 'espresso', 'quantity' => 1]);

    $variant->update(['stock' => 3]);
    $soldOut->update(['stock' => 0]);

    $this->getJson('/cart')
        ->assertJsonPath('items.0.notice', '此規格目前僅剩 3 包，購物車中共有 4 包，請調降數量。')
        ->assertJsonPath('items.1.notice', '此規格目前僅剩 3 包，購物車中共有 4 包，請調降數量。')
        ->assertJsonPath('items.2.notice', '此規格已售完，請移除。');
});

it('sends a 訪客 who goes to checkout to log in, then back to checkout', function () {
    $this->post('/logout');
    $this->get('/checkout')->assertRedirect('/login');

    $this->post('/login', ['email' => 'mei@example.com', 'password' => 'roast-daily-2026'])
        ->assertRedirect('/checkout');
    $this->get('/checkout')->assertOk()->assertSeeText('結帳');
});

it('brings a 訪客 who registers on the way to checkout back to checkout', function () {
    $this->get('/checkout')->assertRedirect('/login');

    $this->post('/register', [
        'email' => 'kai@example.com',
        'password' => 'roast-daily-2026',
        'password_confirmation' => 'roast-daily-2026',
    ])->assertRedirect('/checkout');
});

it('links the checkout button on the 購物車 page to checkout', function () {
    $variant = Variant::factory()->create();
    $this->postJson('/cart/items', ['variant_id' => $variant->id, 'grind' => 'pour_over', 'quantity' => 1]);

    preg_match('/data-vue-component="CartPage"\s+data-props="([^"]*)"/', $this->get('/cart')->getContent(), $matches);
    $props = json_decode(html_entity_decode($matches[1] ?? ''), true, flags: JSON_THROW_ON_ERROR);

    expect($props['urls']['checkout'])->toBe(route('checkout.show'));
});
