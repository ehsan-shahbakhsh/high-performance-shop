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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('price', 15, 4)->nullable();
            $table->decimal('sale_price', 15, 4)->nullable();

            $table->integer('stock_quantity')->default(0);
            $table->string('sku')->nullable()->unique();

            $table->json('attributes');

            $table->string('variant_hash')->index();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'variant_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
