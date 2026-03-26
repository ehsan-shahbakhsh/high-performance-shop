<?php

namespace Database\Factories;

use App\Models\{Product, ProductVariant};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->numberBetween(100_000, 2_000_000);

        $hasSale = fake()->boolean(30);

        $saleStart = $hasSale ? fake()->dateTimeBetween('-5 days', '+5 days') : null;
        $saleEnd = $hasSale ? fake()->dateTimeBetween($saleStart, '+10 days') : null;

        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->bothify('VAR-####'),

            'price' => $price,
            'sale_price' => $hasSale ? fake()->numberBetween($price * 0.5, $price * 0.9) : null,
            'sale_start' => $saleStart,
            'sale_end' => $saleEnd,

            'stock_quantity' => fake()->numberBetween(0, 50),

            'is_active' => true,
            'is_default' => fake()->boolean(20),

            'position' => fake()->numberBetween(0, 100),

            'weight' => fake()->optional()->numberBetween(100, 2000),
            'length' => fake()->optional()->numberBetween(5, 50),
            'width' => fake()->optional()->numberBetween(5, 50),
            'height' => fake()->optional()->numberBetween(5, 50),
        ];
    }
}
