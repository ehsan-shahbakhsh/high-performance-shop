<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ShippingZoneLocationType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_zone_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zone_id')->constrained('shipping_zones')->cascadeOnDelete();

            $table->foreignId('province_id')->nullable()->constrained();
            $table->foreignId('city_id')->nullable()->constrained();

            $table->enum('type', ShippingZoneLocationType::cases())->default(ShippingZoneLocationType::Include);

            $table->timestamps();

            $table->unique(['zone_id', 'province_id', 'city_id'], 'zone_loc_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_zone_locations');
    }
};
