<?php

use App\Models\FlavorNote;
use App\Models\Product;
use Illuminate\Testing\TestResponse;

/**
 * 取得列表 JSON 中咖啡豆名稱，依回傳順序。
 *
 * @param  array<string, mixed>  $query
 * @return list<string>
 */
function browse(array $query = []): array
{
    return test()->getJson('/beans?'.http_build_query($query))
        ->assertOk()
        ->json('products.*.name');
}

/**
 * 解析列表頁交給 Vue 咖啡豆瀏覽元件的初始資料。
 *
 * @return array<string, mixed>
 */
function browserProps(TestResponse $response): array
{
    $response->assertOk()->assertSee('data-vue-component="ProductBrowser"', escape: false);

    preg_match('/data-vue-component="ProductBrowser"\s+data-props="([^"]*)"/', $response->getContent(), $matches);

    return json_decode(html_entity_decode($matches[1] ?? ''), true, flags: JSON_THROW_ON_ERROR);
}

it('filters 咖啡豆 by any of the chosen 產地', function () {
    Product::factory()->create(['name' => '衣索比亞豆', 'origin' => '衣索比亞']);
    Product::factory()->create(['name' => '肯亞豆', 'origin' => '肯亞']);
    Product::factory()->create(['name' => '巴西豆', 'origin' => '巴西']);

    expect(browse(['origin' => ['衣索比亞', '肯亞']]))
        ->toEqualCanonicalizing(['衣索比亞豆', '肯亞豆']);
});

it('requires 咖啡豆 to match every filter group, by 處理法 and 烘焙度', function () {
    Product::factory()->create(['name' => '水洗淺焙', 'process' => '水洗', 'roast' => '淺焙']);
    Product::factory()->create(['name' => '日曬淺焙', 'process' => '日曬', 'roast' => '淺焙']);
    Product::factory()->create(['name' => '水洗深焙', 'process' => '水洗', 'roast' => '深焙']);
    Product::factory()->create(['name' => '蜜處理中焙', 'process' => '蜜處理', 'roast' => '中焙']);

    expect(browse(['process' => ['水洗', '日曬'], 'roast' => ['淺焙']]))
        ->toEqualCanonicalizing(['水洗淺焙', '日曬淺焙']);
});

it('filters 咖啡豆 having any of the chosen 風味標籤', function () {
    $floral = FlavorNote::factory()->create(['name' => '花香']);
    $fruity = FlavorNote::factory()->create(['name' => '果酸']);
    $nutty = FlavorNote::factory()->create(['name' => '堅果']);

    Product::factory()->hasAttached([$floral, $fruity])->create(['name' => '花香果酸豆']);
    Product::factory()->hasAttached($fruity)->create(['name' => '果酸豆']);
    Product::factory()->hasAttached($nutty)->create(['name' => '堅果豆']);
    Product::factory()->create(['name' => '沒有風味標籤的豆子']);

    expect(browse(['flavor' => ['花香', '果酸']]))
        ->toEqualCanonicalizing(['花香果酸豆', '果酸豆']);
});

it('searches 咖啡豆 by keyword in the name or the 風味描述', function () {
    Product::factory()->create(['name' => '衣索比亞 古吉 日曬', 'flavor_description' => '藍莓・玫瑰・紅酒']);
    Product::factory()->create(['name' => '肯亞 涅里 AA', 'flavor_description' => '黑醋栗・葡萄柚']);
    Product::factory()->create(['name' => '巴拿馬 藍莓園', 'flavor_description' => '草莓・蜂蜜']);

    expect(browse(['q' => '藍莓']))->toEqualCanonicalizing(['衣索比亞 古吉 日曬', '巴拿馬 藍莓園'])
        ->and(browse(['q' => ' 涅里 ']))->toBe(['肯亞 涅里 AA']);
});

it('sorts 咖啡豆 newest first by default', function () {
    Product::factory()->create(['name' => '最早上架', 'created_at' => now()->subDays(2)]);
    Product::factory()->create(['name' => '最新上架', 'created_at' => now()]);
    Product::factory()->create(['name' => '昨天上架', 'created_at' => now()->subDay()]);

    expect(browse())->toBe(['最新上架', '昨天上架', '最早上架'])
        ->and(browse(['sort' => 'newest']))->toBe(['最新上架', '昨天上架', '最早上架'])
        ->and(browse(['sort' => '不存在的排序']))->toBe(['最新上架', '昨天上架', '最早上架']);
});

it('sorts 咖啡豆 by their lowest 規格 price', function () {
    Product::factory()
        ->hasVariants(1, ['weight_grams' => 100, 'price' => 320])
        ->hasVariants(1, ['weight_grams' => 250, 'price' => 200])
        ->create(['name' => '最低 200']);
    Product::factory()
        ->hasVariants(1, ['weight_grams' => 100, 'price' => 380])
        ->create(['name' => '最低 380']);
    Product::factory()
        ->hasVariants(1, ['weight_grams' => 100, 'price' => 240])
        ->hasVariants(1, ['weight_grams' => 250, 'price' => 540])
        ->create(['name' => '最低 240']);

    expect(browse(['sort' => 'price_asc']))->toBe(['最低 200', '最低 240', '最低 380'])
        ->and(browse(['sort' => 'price_desc']))->toBe(['最低 380', '最低 240', '最低 200']);
});

it('lists 咖啡豆 without any 規格 last whichever way prices are sorted', function () {
    Product::factory()->create(['name' => '尚無規格']);
    Product::factory()->hasVariants(1, ['price' => 300])->create(['name' => '有規格 300']);
    Product::factory()->hasVariants(1, ['price' => 200])->create(['name' => '有規格 200']);

    expect(browse(['sort' => 'price_asc']))->toBe(['有規格 200', '有規格 300', '尚無規格'])
        ->and(browse(['sort' => 'price_desc']))->toBe(['有規格 300', '有規格 200', '尚無規格']);
});

it('shows each 咖啡豆 with its lowest price and whether every 規格 is sold out', function () {
    Product::factory()
        ->hasVariants(1, ['weight_grams' => 100, 'price' => 260, 'stock' => 0])
        ->hasVariants(1, ['weight_grams' => 250, 'price' => 580, 'stock' => 3])
        ->create(['name' => '還有貨']);
    Product::factory()
        ->hasVariants(1, ['weight_grams' => 100, 'price' => 240, 'stock' => 0])
        ->hasVariants(1, ['weight_grams' => 250, 'price' => 540, 'stock' => 0])
        ->create(['name' => '全部售完']);

    $products = $this->getJson('/beans')->assertOk()->collect('products')->keyBy('name');

    expect($products['還有貨'])->toMatchArray(['min_price' => 260, 'sold_out' => false])
        ->and($products['全部售完'])->toMatchArray(['min_price' => 240, 'sold_out' => true]);
});

it('does not list 咖啡豆 that are not published', function () {
    Product::factory()->create(['name' => '上架中']);
    Product::factory()->create(['name' => '已下架', 'is_published' => false]);

    expect(browse())->toBe(['上架中']);
});

it('pages through the results twelve 咖啡豆 at a time', function () {
    Product::factory()->create(['name' => '最早的豆子', 'origin' => '肯亞', 'created_at' => now()->subYear()]);
    Product::factory()->count(12)->create(['origin' => '肯亞']);
    Product::factory()->count(5)->create(['origin' => '巴西']);

    $this->getJson('/beans?origin[]=肯亞')
        ->assertOk()
        ->assertJsonCount(12, 'products')
        ->assertJsonPath('pagination', ['current_page' => 1, 'last_page' => 2, 'total' => 13]);

    $this->getJson('/beans?origin[]=肯亞&page=2')
        ->assertOk()
        ->assertJsonPath('products.*.name', ['最早的豆子'])
        ->assertJsonPath('pagination', ['current_page' => 2, 'last_page' => 2, 'total' => 13]);
});

it('renders the list page with the filters from the URL so a refresh keeps them', function () {
    Product::factory()->hasVariants(1, ['price' => 320])->create(['name' => '肯亞豆', 'origin' => '肯亞']);
    Product::factory()->create(['name' => '巴西豆', 'origin' => '巴西']);

    $props = browserProps($this->get('/beans?origin[]=肯亞&q=肯亞&sort=price_desc&page=1'));

    expect($props['filters'])->toBe([
        'origin' => ['肯亞'],
        'process' => [],
        'roast' => [],
        'flavor' => [],
        'q' => '肯亞',
        'sort' => 'price_desc',
    ])
        ->and(array_column($props['results']['products'], 'name'))->toBe(['肯亞豆'])
        ->and($props['results']['pagination']['total'])->toBe(1);
});

it('offers each filter option with how many published 咖啡豆 have it', function () {
    $floral = FlavorNote::factory()->create(['name' => '花香']);
    FlavorNote::factory()->create(['name' => '沒有豆子的風味']);

    Product::factory()->hasAttached($floral)->create(['origin' => '肯亞', 'process' => '水洗', 'roast' => '深焙']);
    Product::factory()->hasAttached($floral)->create(['origin' => '肯亞', 'process' => '日曬', 'roast' => '淺焙']);
    Product::factory()->create(['origin' => '巴西', 'process' => '日曬', 'roast' => '中焙']);
    Product::factory()->hasAttached($floral)->create(['origin' => '巴拿馬', 'is_published' => false]);

    $groups = collect(browserProps($this->get('/beans'))['facets'])->keyBy('key');

    expect($groups->keys()->all())->toBe(['flavor', 'origin', 'process', 'roast'])
        ->and($groups['flavor']['options'])->toBe([['value' => '花香', 'count' => 2]])
        ->and($groups['origin']['options'])->toBe([['value' => '肯亞', 'count' => 2], ['value' => '巴西', 'count' => 1]])
        ->and($groups['process']['options'])->toEqualCanonicalizing([['value' => '水洗', 'count' => 1], ['value' => '日曬', 'count' => 2]])
        ->and(array_column($groups['roast']['options'], 'value'))->toBe(['淺焙', '中焙', '深焙']);
});

it('mounts the Vite-bundled 咖啡豆 browser and keeps the HTML and JSON apart in caches', function () {
    $this->get('/beans')
        ->assertOk()
        ->assertSee('/build/assets/app-', escape: false)
        ->assertHeader('Vary', 'Accept');

    $this->getJson('/beans')->assertOk()->assertHeader('Vary', 'Accept');
});

it('treats % and _ in the keyword as literal characters', function () {
    Product::factory()->create(['name' => '100% 阿拉比卡']);
    Product::factory()->create(['name' => '普通的豆子']);

    expect(browse(['q' => '%']))->toBe(['100% 阿拉比卡'])
        ->and(browse(['q' => '_']))->toBe([]);
});
