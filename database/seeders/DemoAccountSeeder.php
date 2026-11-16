<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Customer;
use Illuminate\Database\Seeder;

/**
 * 公開的顧客與店家測試帳號，讓面試官不必註冊就能實際操作前台與後台（US 82）。
 * 帳密是公開的，會寫在 README 與 demo 提示列上。
 *
 * 容器每次啟動都會執行種子資料，所以用 updateOrCreate：不會重複建立，密碼也一律改回公開的那組。
 * 顧客與店家是兩張資料表，同一組帳密只能登入各自的那一邊。
 */
class DemoAccountSeeder extends Seeder
{
    public const CUSTOMER_EMAIL = 'customer@example.com';

    public const ADMIN_EMAIL = 'admin@example.com';

    public const PASSWORD = 'password';

    public function run(): void
    {
        Customer::updateOrCreate(['email' => self::CUSTOMER_EMAIL], ['password' => self::PASSWORD]);
        Admin::updateOrCreate(['email' => self::ADMIN_EMAIL], ['password' => self::PASSWORD]);
    }
}
