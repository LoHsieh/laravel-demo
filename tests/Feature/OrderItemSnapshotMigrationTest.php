<?php

use App\Catalog\Grind;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Variant;

/**
 * 執行 migration 的 DDL 會隱含提交 RefreshDatabase 的交易；RefreshDatabase 偵測到後，下一個測試會重建資料庫。
 */
it('fills in the 咖啡豆 name and 規格 weight of existing 訂單項目 from the current catalog, including deleted 規格', function () {
    $migration = require database_path('migrations/2026_11_17_000001_add_snapshot_to_order_items_table.php');

    $kept = Variant::factory()->for(Product::factory()->create(['name' => '耶加雪菲 孔加 水洗']))
        ->create(['weight_grams' => 250]);
    $deleted = Variant::factory()->for(Product::factory()->create(['name' => '哥倫比亞 慧蘭 水洗']))
        ->create(['weight_grams' => 100]);
    $order = Order::factory()->create();
    $order->items()->createMany([
        [...OrderItem::snapshotOf($kept), 'grind' => Grind::Espresso, 'unit_price' => 420, 'quantity' => 2],
        [...OrderItem::snapshotOf($deleted), 'grind' => Grind::WholeBean, 'unit_price' => 380, 'quantity' => 1],
    ]);
    $deleted->delete();

    $migration->down();
    $migration->up();

    expect($order->items()->get()->map->only('variant_id', 'product_name', 'weight_grams')->all())->toBe([
        ['variant_id' => $kept->id, 'product_name' => '耶加雪菲 孔加 水洗', 'weight_grams' => 250],
        ['variant_id' => $deleted->id, 'product_name' => '哥倫比亞 慧蘭 水洗', 'weight_grams' => 100],
    ]);
});
