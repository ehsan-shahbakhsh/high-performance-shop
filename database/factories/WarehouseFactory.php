<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'انبار ' . fake()->city(),
            'code' => fake()->unique()->bothify('WH-??-##'),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'is_active' => true,
            'is_default' => false,
        ];
    }
}
