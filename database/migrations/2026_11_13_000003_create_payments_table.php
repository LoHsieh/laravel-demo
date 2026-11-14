<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 付款：一筆訂單的一次付款嘗試。付款失敗後重新付款會建立新的一筆，
 * 因為綠界的特店交易編號（MerchantTradeNo）不可重複使用。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // 送往金流服務的交易編號，每次付款嘗試各自不同
            $table->string('merchant_trade_no', 20)->unique();
            // 金流服務的交易編號（綠界 TradeNo），付款通知以它去重
            $table->string('gateway_trade_no')->nullable()->unique();
            $table->string('status');
            $table->unsignedInteger('amount');
            $table->string('result_message')->nullable();
            $table->boolean('simulated')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
