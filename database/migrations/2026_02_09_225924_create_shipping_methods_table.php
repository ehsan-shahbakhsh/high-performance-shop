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
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('carrier_id')->constrained('shipping_carriers');

            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();

            $table->unsignedInteger('min_delivery_time')->nullable()->comment('In Minutes');
            $table->unsignedInteger('max_delivery_time')->nullable()->comment('In Minutes');

            $table->boolean('is_cod_supported')->default(false);

            $table->unsignedInteger('max_weight')->nullable()->comment('Maximum allowed weight in grams');

            $table->json('settings')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);

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
        Schema::dropIfExists('shipping_methods');
    }
};
