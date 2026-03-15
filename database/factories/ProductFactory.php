<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\AttributeSet;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        $attributes = [
            'technical_specs' => [
                'label' => 'مشخصات فنی',
                'items' => [
                    'cpu' => ['label' => 'پردازنده', 'value' => fake()->word()],
                    'ram' => ['label' => 'رم', 'value' => fake()->randomElement(['8GB', '16GB'])],
                ]
            ]
        ];

        return [
            'attribute_set_id' => AttributeSet::factory(),
            'brand_id' => Brand::factory(),

            'name' => $name,
            'slug' => Str::slug($name),

            'sku' => fake()->unique()->bothify('PROD-####'),
            'type' => fake()->randomElement(ProductType::cases()),
            'price' => fake()->numberBetween(100, 1000) * 1000,

            'is_active' => true,
            'manage_stock' => false,

            'attributes' => $attributes,

            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),

            'thumbnail' => fake()->imageUrl(600, 600, 'product'),

            'seo_title' => $name,
            'seo_description' => fake()->realText(150),
        ];
    }

    public function variable(): static
    {
        return $this->state(['type' => ProductType::Variable]);
    }

    public function simple(): static
    {
        return $this->state(['type' => ProductType::Simple]);
    }
}
