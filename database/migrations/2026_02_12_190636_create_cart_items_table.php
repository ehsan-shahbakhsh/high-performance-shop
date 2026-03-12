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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->foreignUlid('cart_id')->constrained()->cascadeOnDelete();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            $table->unsignedInteger('quantity')->default(1);

            $table->unsignedBigInteger('variant_key')->virtualAs('COALESCE(`variant_id`, 0)');

            $table->unsignedBigInteger('unit_price_snapshot');

            $table->timestamps();

            $table->index(['cart_id', 'product_id']);

            $table->unique(['cart_id', 'product_id', 'variant_key'], 'uniq_cart_product_variant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
