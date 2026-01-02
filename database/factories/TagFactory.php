<?php

namespace Database\Factories;

use App\Enums\TagType;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
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

            'type' => fake()->randomElement(TagType::cases()),

            'color' => fake()->optional(0.7)->hexColor(),
            'icon'  => fake()->optional(0.3)->randomElement(['heroicon-o-tag', 'heroicon-o-gift', 'heroicon-o-fire']),

            'description' => fake()->optional(0.6)->paragraph(2),

            'seo_title' => fake()->optional(0.8)->sentence(3),
            'seo_description' => fake()->optional(0.8)->text(150),

            'canonical_url' => fake()->optional(0.1)->url(),

            'usage_count' => fake()->numberBetween(0, 200),
            'position'    => fake()->numberBetween(0, 100),

            'is_visible'  => fake()->boolean(90),
            'is_featured' => fake()->boolean(15),
        ];
    }
}
