<?php

namespace Database\Seeders;

use App\Models\FlavorNote;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    private const FLAVOR_NOTES = ['花香', '果酸', '堅果', '巧克力'];

    public function run(): void
    {
        // 正式環境每次啟動都會執行種子，因此以名稱與重量比對，避免重複建立。
        $flavorNotes = collect(self::FLAVOR_NOTES)
            ->mapWithKeys(fn (string $name) => [$name => FlavorNote::firstOrCreate(['name' => $name])->id]);

        // 依上架先後排列，最後一支是最新上架。
        foreach ($this->products() as [$attributes, $flavors, $variants]) {
            $product = Product::firstOrCreate(['name' => $attributes['name']], $attributes);

            $product->flavorNotes()->syncWithoutDetaching($flavorNotes->only($flavors)->values());

            foreach ($variants as $variant) {
                $product->variants()->firstOrCreate(['weight_grams' => $variant['weight_grams']], $variant);
            }
        }
    }

    /**
     * @return list<array{array<string, string|bool>, list<string>, list<array<string, int>>}>
     */
    private function products(): array
    {
        return [
            [
                ['name' => '巴西 喜拉朵 日曬', 'origin' => '巴西', 'process' => '日曬', 'roast' => '深焙', 'flavor_description' => '黑巧克力・花生・黑糖'],
                ['巧克力', '堅果'],
                [
                    ['weight_grams' => 100, 'price' => 220, 'stock' => 20],
                    ['weight_grams' => 250, 'price' => 500, 'stock' => 12],
                    ['weight_grams' => 500, 'price' => 920, 'stock' => 6],
                ],
            ],
            [
                ['name' => '瓜地馬拉 安提瓜 日曬', 'origin' => '瓜地馬拉', 'process' => '日曬', 'roast' => '中深焙', 'flavor_description' => '可可・黑糖・烤堅果'],
                ['巧克力', '堅果'],
                [
                    ['weight_grams' => 100, 'price' => 240, 'stock' => 0],
                    ['weight_grams' => 250, 'price' => 540, 'stock' => 0],
                ],
            ],
            [
                ['name' => '哥斯大黎加 塔拉珠 蜜處理', 'origin' => '哥斯大黎加', 'process' => '蜜處理', 'roast' => '中焙', 'flavor_description' => '蜂蜜・榛果・牛奶巧克力'],
                ['堅果', '巧克力'],
                [
                    ['weight_grams' => 100, 'price' => 340, 'stock' => 8],
                    ['weight_grams' => 250, 'price' => 760, 'stock' => 4],
                ],
            ],
            [
                ['name' => '哥倫比亞 慧蘭 水洗', 'origin' => '哥倫比亞', 'process' => '水洗', 'roast' => '中焙', 'flavor_description' => '焦糖・蘋果・杏仁'],
                ['堅果'],
                [
                    ['weight_grams' => 100, 'price' => 240, 'stock' => 15],
                    ['weight_grams' => 250, 'price' => 540, 'stock' => 9],
                    ['weight_grams' => 500, 'price' => 1000, 'stock' => 4],
                ],
            ],
            [
                ['name' => '巴拿馬 波奎特 日曬', 'origin' => '巴拿馬', 'process' => '日曬', 'roast' => '淺中焙', 'flavor_description' => '草莓・蜂蜜・可可'],
                ['果酸', '巧克力'],
                [
                    ['weight_grams' => 100, 'price' => 380, 'stock' => 6],
                    ['weight_grams' => 250, 'price' => 860, 'stock' => 3],
                ],
            ],
            [
                ['name' => '肯亞 涅里 AA 水洗', 'origin' => '肯亞', 'process' => '水洗', 'roast' => '淺中焙', 'flavor_description' => '黑醋栗・葡萄柚・蔗糖'],
                ['果酸'],
                [
                    ['weight_grams' => 100, 'price' => 320, 'stock' => 10],
                    ['weight_grams' => 250, 'price' => 720, 'stock' => 8],
                ],
            ],
            [
                ['name' => '衣索比亞 古吉 日曬', 'origin' => '衣索比亞', 'process' => '日曬', 'roast' => '淺焙', 'flavor_description' => '藍莓・玫瑰・紅酒'],
                ['花香', '果酸'],
                [
                    ['weight_grams' => 100, 'price' => 300, 'stock' => 9],
                    ['weight_grams' => 250, 'price' => 680, 'stock' => 5],
                    ['weight_grams' => 500, 'price' => 1240, 'stock' => 2],
                ],
            ],
            [
                ['name' => '耶加雪菲 孔加 水洗', 'origin' => '衣索比亞', 'process' => '水洗', 'roast' => '淺焙', 'flavor_description' => '茉莉・檸檬・紅茶', 'is_featured' => true],
                ['花香', '果酸'],
                [
                    ['weight_grams' => 100, 'price' => 260, 'stock' => 12],
                    ['weight_grams' => 250, 'price' => 580, 'stock' => 6],
                    ['weight_grams' => 500, 'price' => 1080, 'stock' => 0],
                ],
            ],
        ];
    }
}
