<?php

namespace Database\Factories;

use App\Enums\ShippingZoneLocationType;
use App\Models\City;
use App\Models\Province;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingZoneLocation>
 */
class ShippingZoneLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zone_id' => ShippingZone::factory(),
            'province_id' => Province::factory(),
            'city_id' => City::factory(),
            'type' => fake()->randomElement(ShippingZoneLocationType::cases()),
        ];
    }

    public function wholeProvince(): static
    {
        return $this->state(fn (array $attributes) => [
            'city_id' => null,
        ]);
    }

    public function excluded(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ShippingZoneLocationType::Exclude,
        ]);
    }
}
