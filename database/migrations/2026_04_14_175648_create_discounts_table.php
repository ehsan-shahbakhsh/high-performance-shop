<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{DiscountType, DiscountScope, DiscountConditionMatchType};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_automatic')->default(false);

            $table->enum('type', DiscountType::cases());
            $table->enum('scope', DiscountScope::cases());

            $table->decimal('amount', 16)->unsigned();
            $table->unsignedBigInteger('max_discount_amount')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->json('action_settings')->nullable();

            $table->enum('condition_match_type', DiscountConditionMatchType::cases())->default(DiscountConditionMatchType::All);

            $table->boolean('is_exclusive')->default(false);
            $table->boolean('is_active');
            $table->unsignedInteger('priority')->default(0);

            $table->unsignedBigInteger('target_variant_id_idx')
                ->storedAs('json_unquote(json_extract(action_settings, "$.target_variant_id"))')
                ->nullable()
                ->index();

            $table->string('strategy_idx')
                ->virtualAs('json_unquote(json_extract(action_settings, "$.strategy"))')
                ->nullable()
                ->index();

            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('user_usage_limit')->nullable();
            $table->unsignedInteger('used')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unsignedTinyInteger('unique_keeper')
                ->virtualAs('IF(deleted_at IS NULL, 1, NULL)');

            $table->unique(['name', 'unique_keeper']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
