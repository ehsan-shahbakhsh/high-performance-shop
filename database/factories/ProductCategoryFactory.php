<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(rand(1, 2), true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),

            'parent_id' => null,

            'icon' => fake()->randomElement(['fa-mobile', 'fa-laptop', 'fa-tshirt', 'fa-home', 'fa-book']),

            'cover' => null,

            'is_active' => fake()->boolean(90),
            'is_featured' => fake()->boolean(20),
            'include_in_menu' => fake()->boolean(70),

            'position' => fake()->numberBetween(0, 100),

            'seo_title' => 'خرید آنلاین ' . $name,
            'seo_description' => fake()->sentence(10),
        ];
    }

    public function childOf(ProductCategory $parent): static
    {
        return $this->state(fn(array $attributes) => ['parent_id' => $parent->id]);
    }

    public function featured(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_featured' => true,
            'cover' => 'categories/featured-placeholder.jpg',
        ]);
    }
}
