<?php

namespace Database\Factories;

use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Province>
 */
class ProvinceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nameEn = fake()->unique()->city();

        return [
            'name' => 'استان ' . fake()->unique()->firstName(),
            'name_en' => $nameEn,
            'slug' => Str::slug($nameEn),
            'tel_prefix' => '0' . fake()->numberBetween(11, 99),
            'latitude' => fake()->latitude(25, 40),
            'longitude' => fake()->longitude(44, 64),
            'is_active' => true,
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
