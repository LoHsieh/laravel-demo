<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 出貨時間：已出貨的訂單在出貨滿 7 天後由排程改為已完成。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('shipped_at')->nullable()->after('payment_due_at');

            $table->index(['status', 'shipped_at']);
        });

        // 既有的已出貨訂單沒有出貨時間，以最後更新時間代替，否則永遠不會自動完成
        DB::table('orders')->where('status', 'shipped')->whereNull('shipped_at')
            ->update(['shipped_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'shipped_at']);
            $table->dropColumn('shipped_at');
        });
    }
};
