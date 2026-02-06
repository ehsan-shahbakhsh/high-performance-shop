<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
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
            'province_id' => Province::factory(),

            'name' => 'شهر ' . fake()->firstName(),
            'name_en' => $nameEn,
            'slug' => Str::slug($nameEn),

            'latitude' => fake()->latitude(25, 40),
            'longitude' => fake()->longitude(44, 64),

            'is_active' => true,
            'has_shipping' => fake()->boolean(90),
        ];
    }
}
