<?php

namespace Database\Factories;

use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingRate>
 */
class ShippingRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $basePrice = fake()->numberBetween(25, 150) * 1000;

        return [
            'shipping_zone_id' => ShippingZone::factory(),
            'shipping_method_id' => ShippingMethod::factory(),

            'is_active' => fake()->boolean(90),
            'position' => fake()->numberBetween(0, 100),

            'base_price' => $basePrice,

            'price_per_kg' => fake()->optional(0.2)->numberBetween(5, 50) * 1000,

            'free_shipping_over' => fake()->optional(0.3)->numberBetween(10, 50) * 100000,

            'cod_fee' => fake()->optional(0.1)->numberBetween(5, 20) * 1000,

            'min_weight' => 0,
            'max_weight' => fake()->optional(0.5)->randomElement([10000, 20000, 30000]),

            'min_subtotal' => 0,
            'max_subtotal' => null,

            'min_delivery_time' => null,
            'max_delivery_time' => null,

            'conditions' => null,
        ];
    }

    public function free(): static
    {
        return $this->state(fn(array $attributes) => [
            'base_price' => 0,
            'price_per_kg' => 0,
            'min_subtotal' => 0,
        ]);
    }

    public function heavyCargo(): static
    {
        return $this->state(fn(array $attributes) => [
            'base_price' => 200000,
            'price_per_kg' => 15000,
            'min_weight' => 10000,
            'max_weight' => 100000,
        ]);
    }

    public function withTimeOverride(): static
    {
        return $this->state(fn(array $attributes) => [
            'min_delivery_time' => 1440,
            'max_delivery_time' => 2880,
        ]);
    }
}
