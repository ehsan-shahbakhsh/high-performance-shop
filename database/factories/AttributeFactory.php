<?php

namespace Database\Factories;

use App\Enums\AttributeType;
use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'code' => Str::slug($name),

            'type' => fake()->randomElement(AttributeType::cases()),

            'is_filterable' => fake()->boolean(40),
            'is_required' => fake()->boolean(40),
        ];
    }

    public function color(): static
    {
        $color = fake()->unique()->numberBetween(1, 999);

        return $this->state(fn (array $attributes) => [
            'type' => AttributeType::Color,
            'name' => 'Color_' . $color,
            'code' => Str::slug('Color_' . $color),
        ]);
    }
}
