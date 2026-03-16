<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();

            $table->foreignId('variant_id')->nullable()->constrained('product_variants');
            $table->unsignedBigInteger('variant_key')->virtualAs('COALESCE(`variant_id`, 0)');

            $table->timestamps();

            $table->unique(['wishlist_id', 'product_id', 'variant_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
