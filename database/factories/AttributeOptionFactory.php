<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttributeOption>
 */
class AttributeOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = fake()->word();

        return [
            'attribute_id' => Attribute::factory(),
            'label' => ucfirst($word),
            'value' => $word,
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
