<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('sku')->nullable();
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->json('selected_options')->nullable();
            $table->json('snapshot')->nullable();

            $table->unsignedInteger('quantity')->default(1);

            $table->unsignedBigInteger('original_unit_price');
            $table->unsignedBigInteger('sale_unit_price')->nullable();
            $table->unsignedBigInteger('promotion_discount')->default(0);
            $table->unsignedBigInteger('final_unit_price');
            $table->unsignedBigInteger('line_total');

            $table->timestamps();
            $table->softDeletes();

            $table->unsignedTinyInteger('unique_keeper')
                ->virtualAs('IF(deleted_at IS NULL, 1, NULL)');

            $table->unique(['order_id', 'product_variant_id', 'unique_keeper']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
