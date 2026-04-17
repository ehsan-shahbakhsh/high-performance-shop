<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Discount;
use App\Models\Discountable;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discountable>
 */
class DiscountableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $discountableClass = fake()->randomElement([
            Product::class,
            ProductCategory::class,
            Brand::class,
        ]);

        return [
            'discount_id' => Discount::factory(),

            'discountable_type' => $discountableClass,
            'discountable_id' => $discountableClass::factory(),

            'is_excluded' => fake()->boolean(10),
        ];
    }
}
