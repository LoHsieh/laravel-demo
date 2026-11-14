<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

/**
 * 端對端測試（Playwright）的資料：商品目錄加上一位可登入的顧客。
 * 只在端對端測試的獨立資料庫使用；正式環境啟動時執行的是 DatabaseSeeder。
 */
class EndToEndSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProductSeeder::class);

        Customer::firstOrCreate(['email' => 'e2e@example.com'], ['password' => 'password']);
    }
}
