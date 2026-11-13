<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 顧客的購物車項目。訪客的購物車存在 session，顧客的存在這裡，登出再登入、換裝置都還在。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained()->cascadeOnDelete();
            $table->string('grind');
            $table->unsignedInteger('quantity');
            // 顧客上次看到的價格，只用來提示價格變動；金額一律以規格目前的價格為準
            $table->unsignedInteger('seen_price')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'variant_id', 'grind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
