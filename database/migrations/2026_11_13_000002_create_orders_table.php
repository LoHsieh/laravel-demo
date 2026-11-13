<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 訂單與訂單項目。訂單項目保存下單當下的單價，之後調價不影響訂單金額。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // 顯示給顧客的訂單編號。TiDB 的 AUTO_INCREMENT 會跳號，不拿 id 給顧客看（ADR 0004）
            $table->string('number', 20)->unique();
            $table->foreignId('customer_id')->constrained();
            $table->string('status');
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->string('recipient_address');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('shipping_fee');
            $table->unsignedInteger('total');
            $table->timestamp('payment_due_at');
            $table->timestamps();

            $table->index(['status', 'payment_due_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained();
            $table->string('grind');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
