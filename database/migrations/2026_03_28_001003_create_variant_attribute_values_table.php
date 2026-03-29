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
        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained();

            $table->foreignId('attribute_option_id')->nullable()->constrained();

            $table->string('value_string')->nullable();
            $table->decimal('value_number', 15, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();

            $table->timestamps();

            $table->unique(['product_id', 'product_variant_id', 'attribute_id'], 'vav_product_variant_attribute_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_attribute_values');
    }
};
