<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProductBundleItemModifierType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_bundle_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('child_variant_id')->constrained('product_variants')->cascadeOnDelete();

            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_required')->default(true);

            $table->enum('modifier_type', ProductBundleItemModifierType::cases())->default(ProductBundleItemModifierType::None);
            $table->unsignedBigInteger('price_modifier')->default(0);

            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(['parent_variant_id', 'child_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_bundle_items');
    }
};
