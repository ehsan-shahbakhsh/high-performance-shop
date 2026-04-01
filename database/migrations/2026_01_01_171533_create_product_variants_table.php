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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedBigInteger('price')
                ->comment('Base price. Always required; sale_price is optional.');
            $table->unsignedBigInteger('sale_price')->nullable()
                ->comment('Optional sale price. If null, no discount is applied.');

            $table->timestamp('sale_start')->nullable()
                ->comment('Sale schedule start. Applies to this variant only.');
            $table->timestamp('sale_end')->nullable()
                ->comment('Sale schedule end. Applies to this variant only.');

            $table->unsignedInteger('stock_quantity')->default(0)
                ->comment('Denormalized stock synced from inventories table.');
            $table->string('sku', 100)->nullable();

            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();

            $table->unsignedInteger('position')->default(0);

            $table->decimal('weight', 10)->nullable()->unsigned()
                ->comment("Net shipping weight of this variant (grams)");

            $table->decimal('length', 10)->nullable()->unsigned()->comment("Variant length (cm)");
            $table->decimal('width', 10)->nullable()->unsigned()->comment("Variant width (cm)");
            $table->decimal('height', 10)->nullable()->unsigned()->comment("Variant height (cm)");

            $table->string('signature');

            $table->timestamps();
            $table->softDeletes();

            $table->unsignedTinyInteger('unique_keeper')
                ->virtualAs('IF(deleted_at IS NULL, 1, NULL)');

            $table->unique(['sku', 'unique_keeper']);
            $table->unique(['product_id', 'unique_keeper', 'signature']);
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
