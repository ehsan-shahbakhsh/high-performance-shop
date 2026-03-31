<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\{Product, ProductBundleItem, ProductVariant};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductBundleItem>
 */
class ProductBundleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_variant_id' => ProductVariant::factory()
                ->for(Product::factory()->state([
                    'type' => ProductType::Bundle,
                ])),

            'child_variant_id' => ProductVariant::factory(),

            'quantity' => fake()->numberBetween(1, 50),

            'price_modifier' => fake()->numberBetween(-100_000, 100_000),

            'position' => fake()->numberBetween(0, 100),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function ($model) {
            if ($model->parent_variant_id === $model->child_variant_id) {
                $model->child_variant_id = ProductVariant::factory()->create()->id;
            }
        });
    }
}
