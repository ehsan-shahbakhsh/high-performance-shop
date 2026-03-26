<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{ProductType, ProductStatus, ProductOutOfStockAction};

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('type', ProductType::cases())->index();
            $table->string('name');
            $table->string('slug');

            $table->enum('status', ProductStatus::cases())->index();

            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_virtual')->default(false)->index();
            $table->boolean('is_downloadable')->default(false)->index();
            $table->boolean('manage_stock')->default(false);

            $table->enum('out_of_stock_action', ProductOutOfStockAction::cases())->default(ProductOutOfStockAction::Default)
                ->comment('Defines behavior when product is out of stock (default system behavior, hide product, or show custom text).');
            $table->string('custom_stock_text')->nullable()
                ->comment("Custom message shown when product is out of stock (used only when out_of_stock_action = 'text').");

            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();

            $table->string('seo_title', 60)->nullable();
            $table->string('seo_description', 160)->nullable();

            $table->timestamp('published_at')->nullable()
                ->comment('Publish timestamp; product becomes publicly visible at this time (set manually for scheduling or automatically when published).');

            $table->unsignedBigInteger('min_price')->default(0)
                ->comment('Denormalized. Real prices stored in product_variants.');
            $table->unsignedBigInteger('max_price')->default(0)
                ->comment('Denormalized. Real prices stored in product_variants.');
            $table->unsignedBigInteger('min_sale_price')->default(0)
                ->comment('Denormalized. Real prices stored in product_variants.');
            $table->unsignedBigInteger('max_sale_price')->default(0)
                ->comment('Denormalized. Real prices stored in product_variants.');

            $table->timestamps();
            $table->softDeletes();

            $table->unsignedTinyInteger('unique_keeper')
                ->virtualAs('IF(deleted_at IS NULL, 1, NULL)');

            $table->unique(['slug', 'unique_keeper']);
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
