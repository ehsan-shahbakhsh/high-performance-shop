<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\Discount;
use App\Models\DiscountUsage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountUsage>
 */
class DiscountUsageFactory extends Factory
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
            'user_id' => User::factory(),
            'order_id' => Order::factory(),

            'coupon_id' => null,

            'amount' => fake()->numberBetween(10, 500) * 1000,
        ];
    }

    public function withCoupon(): static
    {
        return $this->state(fn(array $attributes) => [
            'coupon_id' => function (array $attributes) {
                return Coupon::factory()->create([
                    'discount_id' => $attributes['discount_id']
                ])->id;
            },
        ]);
    }
}
