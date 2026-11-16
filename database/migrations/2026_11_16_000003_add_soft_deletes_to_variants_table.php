<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 店家刪除規格時只做軟刪除，既有訂單仍能顯示它（ADR 0006）。
 * 同一支咖啡豆同一重量仍只有一筆規格（含已刪除的），再次新增同一重量時恢復已刪除的那筆。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
