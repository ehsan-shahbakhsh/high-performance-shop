<?php

namespace Database\Factories;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeGroup;
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
            'attribute_group_id' => null,

            'name' => ucfirst($name),
            'code' => Str::slug($name),

            'type' => fake()->randomElement(AttributeType::cases()),

            'is_filterable' => fake()->boolean(40),
            'is_required' => fake()->boolean(40),
            'is_variant' => fake()->boolean(40),

            'position' => fake()->numberBetween(0, 100),
        ];
    }

    public function withGroup(?AttributeGroup $group = null): static
    {
        return $this->state(function () use ($group) {
            $group ??= AttributeGroup::factory()->create();

            return ['attribute_group_id' => $group->id];
        });
    }
}
