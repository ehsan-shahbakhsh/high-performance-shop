<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),

            'website' => 'https://www.' . Str::slug($name) . '.com',
            'description' => fake()->realText(),

            'logo' => 'brands/logos/default.png',
            'cover' => 'brands/covers/default.jpg',

            'is_active' => fake()->boolean(90),
            'is_featured' => fake()->boolean(15),

            'position' => fake()->numberBetween(0, 100),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_featured' => true,
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
