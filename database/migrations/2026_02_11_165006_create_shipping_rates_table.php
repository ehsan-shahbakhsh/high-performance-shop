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
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);

            $table->unsignedBigInteger('base_price')->default(0);
            $table->unsignedBigInteger('price_per_kg')->nullable();
            $table->unsignedBigInteger('free_shipping_over')->nullable();
            $table->unsignedBigInteger('cod_fee')->default(0);

            $table->unsignedInteger('min_weight')->nullable();
            $table->unsignedInteger('max_weight')->nullable();
            $table->unsignedBigInteger('min_subtotal')->nullable();
            $table->unsignedBigInteger('max_subtotal')->nullable();

            $table->unsignedInteger('min_delivery_time')->nullable()->comment('Override in Minutes');
            $table->unsignedInteger('max_delivery_time')->nullable()->comment('Override in Minutes');

            $table->json('conditions')->nullable();

            $table->timestamps();

            $table->unique(['shipping_method_id', 'shipping_zone_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
