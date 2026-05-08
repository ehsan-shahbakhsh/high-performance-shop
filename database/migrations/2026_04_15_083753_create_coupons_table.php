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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();

            $table->string('code');

            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('user_usage_limit')->nullable();
            $table->unsignedInteger('used')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unsignedTinyInteger('unique_keeper')
                ->virtualAs('IF(deleted_at IS NULL, 1, NULL)');

            $table->unique(['code', 'unique_keeper']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
