<?php

namespace Database\Factories;

use App\Enums\ProductRelationType;
use App\Models\Product;
use App\Models\ProductRelation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductRelation>
 */
class ProductRelationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'related_product_id' => Product::factory(),
            'type' => fake()->randomElement(ProductRelationType::cases()),
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
