<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
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
        $attributes = [
            'color' => fake()->colorName(),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
        ];

        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->bothify('VAR-####'),
            'price' => fake()->numberBetween(100, 1000) * 1000,
            'stock_quantity' => 0,
            'attributes' => $attributes,
            'is_active' => true,
            'variant_hash' => ProductVariant::generateHash($attributes),
        ];
    }
}
