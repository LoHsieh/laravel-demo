<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 訂單項目保存下單當下的咖啡豆名稱與規格重量（ADR 0007）。
 * 既有的訂單項目以目前的規格與咖啡豆補上（包含已刪除的規格，資料庫查詢不受軟刪除範圍影響）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_name')->nullable()->after('variant_id');
            $table->unsignedInteger('weight_grams')->nullable()->after('product_name');
        });

        DB::table('order_items')
            ->join('variants', 'variants.id', '=', 'order_items.variant_id')
            ->join('products', 'products.id', '=', 'variants.product_id')
            ->update([
                'order_items.product_name' => DB::raw('products.name'),
                'order_items.weight_grams' => DB::raw('variants.weight_grams'),
            ]);

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_name')->nullable(false)->change();
            $table->unsignedInteger('weight_grams')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['product_name', 'weight_grams']);
        });
    }
};
