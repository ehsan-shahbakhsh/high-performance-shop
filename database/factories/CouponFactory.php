<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discount_id' => Discount::factory(),

            'code' => str(fake()->unique()->word())->upper() . '-' . fake()->numberBetween(100, 999),

            'usage_limit' => fake()->optional(0.7)->numberBetween(10, 500),
            'user_usage_limit' => fake()->optional(0.8)->numberBetween(1, 3),

            'is_active' => fake()->boolean(80),

            'expires_at' => fake()->optional()->dateTime('+1 month'),
        ];
    }
}
