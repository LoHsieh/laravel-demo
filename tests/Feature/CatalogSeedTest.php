<?php

use App\Catalog\Roast;
use App\Models\FlavorNote;
use App\Models\Product;
use App\Models\Variant;
use Database\Seeders\DatabaseSeeder;

it('seeds enough 咖啡豆 to show off every filter on the list', function () {
    $this->seed(DatabaseSeeder::class);

    $this->getJson('/beans?flavor[]=巧克力&sort=price_asc')
        ->assertOk()
        ->assertJsonPath('products.*.name', ['巴西 喜拉朵 日曬', '瓜地馬拉 安提瓜 日曬', '哥斯大黎加 塔拉珠 蜜處理', '巴拿馬 波奎特 日曬']);

    $this->getJson('/beans?q=耶加雪菲')
        ->assertOk()
        ->assertJsonPath('products.0.min_price', 260)
        ->assertJsonPath('products.0.sold_out', false);

    $this->getJson('/beans?q=瓜地馬拉')
        ->assertOk()
        ->assertJsonPath('products.0.sold_out', true);
});

it('features a seeded 咖啡豆 on the home page', function () {
    $this->seed(DatabaseSeeder::class);

    $this->get('/')->assertOk()->assertSeeText('本週精選：耶加雪菲 孔加 水洗');
});

it('does not duplicate seeded data when the seeder runs again on the next deploy', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Product::count())->toBe(8)
        ->and(Variant::count())->toBe(20)
        ->and(FlavorNote::count())->toBe(4)
        ->and(Product::firstWhere('name', '耶加雪菲 孔加 水洗')->flavorNotes()->count())->toBe(2);
});

it('gives every seeded and factory-made 咖啡豆 a known 烘焙度', function () {
    $this->seed(DatabaseSeeder::class);
    Product::factory()->count(5)->create();

    expect(Product::all()->pluck('roast'))->each->toBeInstanceOf(Roast::class);
});
