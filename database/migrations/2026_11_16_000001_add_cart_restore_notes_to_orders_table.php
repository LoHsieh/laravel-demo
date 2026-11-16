<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 付款逾期時把訂單項目放回購物車的結果。排程沒有 session 可以提示顧客，所以存在訂單上，
 * 於訂單詳情顯示。null 表示訂單不是因付款逾期而取消；空陣列表示全部原樣放回。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('cart_restore_notes')->nullable()->after('payment_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('cart_restore_notes');
        });
    }
};
