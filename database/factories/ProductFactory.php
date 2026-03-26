<?php

namespace Database\Factories;

use App\Enums\{ProductOutOfStockAction, ProductType, ProductStatus};
use App\Models\{Product, Brand};
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

        $status = fake()->randomElement(ProductStatus::cases());

        $isVirtual = fake()->boolean(20);
        $isDownloadable = $isVirtual && fake()->boolean();

        return [
            'brand_id' => Brand::factory(),

            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(100, 9999),

            'type' => fake()->randomElement(ProductType::cases()),
            'status' => $status,

            'is_active' => true,

            'is_virtual' => $isVirtual,
            'is_downloadable' => $isDownloadable,

            'manage_stock' => fake()->boolean(70),

            'out_of_stock_action' => fake()->randomElement(ProductOutOfStockAction::cases()),
            'custom_stock_text' => fake()->optional()->sentence(),

            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),

            'seo_title' => $name,
            'seo_description' => fake()->realText(150),

            'published_at' => $status === ProductStatus::Published
                ? fake()->dateTimeBetween('-1 month')
                : null,

            'min_price' => null,
            'max_price' => null,
            'min_sale_price' => null,
            'max_sale_price' => null,
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

    public function published(): static
    {
        return $this->state([
            'status' => ProductStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state([
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);
    }
}
