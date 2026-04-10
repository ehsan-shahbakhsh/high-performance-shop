<?php

namespace Database\Factories;

use App\Models\{Address, City, User};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            'recipient_first_name' => fake()->firstName(),
            'recipient_last_name' => fake()->lastName(),
            'recipient_mobile' => '09' . fake()->numerify('#########'),

            'city_id' => City::factory(),
            'province_id' => function (array $attributes) {
                return City::query()->find($attributes['city_id'])->province_id;
            },

            'title' => fake()->optional()->word(),
            'address_line' => fake()->streetAddress(),

            'plaque' => fake()->optional()->numerify('##'),
            'unit' => fake()->optional()->numerify('#'),
            'postal_code' => fake()->numerify('##########'),

            'latitude' => fake()->latitude(25, 40),
            'longitude' => fake()->longitude(44, 64),

            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
