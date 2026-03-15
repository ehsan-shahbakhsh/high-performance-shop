<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProductOutOfStockAction;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attribute_set_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('type')->index();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('slug');

            $table->decimal('price', 15, 4)->nullable()->index();
            $table->decimal('sale_price', 15, 4)->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->boolean('manage_stock')->default(false);

            $table->enum('out_of_stock_action', ProductOutOfStockAction::cases())->default(ProductOutOfStockAction::Default);
            $table->string('custom_stock_text')->nullable();

            $table->json('attributes')->nullable();

            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();

            $table->string('seo_title', 60)->nullable();
            $table->string('seo_description', 160)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unsignedTinyInteger('unique_keeper')
                ->virtualAs('IF(deleted_at IS NULL, 1, NULL)');

            $table->unique(['slug', 'unique_keeper']);
            $table->unique(['sku', 'unique_keeper']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
