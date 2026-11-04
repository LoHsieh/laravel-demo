<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flavor_notes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('flavor_note_product', function (Blueprint $table) {
            $table->foreignId('flavor_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->primary(['flavor_note_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flavor_note_product');
        Schema::dropIfExists('flavor_notes');
    }
};
