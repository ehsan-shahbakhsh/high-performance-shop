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
        $name = $this->faker->unique()->words(rand(1, 2), true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),

            'parent_id' => null,

            'icon' => $this->faker->randomElement(['fa-mobile', 'fa-laptop', 'fa-tshirt', 'fa-home', 'fa-book']),

            'cover_image' => null,

            'is_active' => $this->faker->boolean(90),
            'is_featured' => $this->faker->boolean(20),
            'include_in_menu' => $this->faker->boolean(70),

            'position' => $this->faker->numberBetween(0, 100),

            'seo_title' => 'خرید آنلاین ' . $name,
            'seo_description' => $this->faker->sentence(10),
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
            'cover_image' => 'categories/featured-placeholder.jpg',
        ]);
    }
}
