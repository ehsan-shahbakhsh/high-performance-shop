<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductFile>
 */
class ProductFileFactory extends Factory
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
            'display_name' => fake()->words(3, true),
            'filename' => fake()->uuid() . '.pdf',
            'disk' => 'local',
            'storage_path' => 'private/products/' . fake()->uuid() . '.pdf',
            'size' => fake()->numberBetween(1024, 104857600),
            'download_limit' => fake()->optional(0.3)->numberBetween(1, 10),
            'expiry_days' => fake()->optional(0.2)->numberBetween(7, 365),
            'position' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
