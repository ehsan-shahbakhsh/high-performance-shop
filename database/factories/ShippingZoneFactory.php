<?php

namespace Database\Factories;

use App\Enums\ShippingZoneLocationType;
use App\Models\Province;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingZone>
 */
class ShippingZoneFactory extends Factory
{
    protected array $zoneNames = [
        'تهران و حومه',
        'مراکز استان‌ها',
        'شهرهای شمالی (گیلان و مازندران)',
        'مناطق آزاد (کیش، قشم، چابهار)',
        'سایر شهرستان‌ها',
        'مناطق دور افتاده',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
            $name = fake()->unique()->randomElement($this->zoneNames);

        return [
            'name' => $name,
            'code' => 'zone_' . fake()->unique()->numberBetween(100, 999),
            'position' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    public function withLocations(): static
    {
        return $this->afterCreating(function (ShippingZone $zone) {
            if (str_contains($zone->name, 'تهران')) {
                $tehran = Province::query()->where('name', 'like', '%تهران%')->first();
                if ($tehran) {
                    $zone->locations()->create([
                        'province_id' => $tehran->id,
                        'city_id' => null,
                        'type' => ShippingZoneLocationType::Include,
                    ]);
                }
                return;
            }

            $provinces = Province::query()->inRandomOrder()->limit(3)->get();

            foreach ($provinces as $province) {
                $zone->locations()->create([
                    'province_id' => $province->id,
                    'city_id' => null,
                    'type' => ShippingZoneLocationType::Include,
                ]);

                $randomCity = $province->cities()->inRandomOrder()->first();
                if ($randomCity && fake()->boolean(30)) {
                    $zone->locations()->create([
                        'province_id' => $province->id,
                        'city_id' => $randomCity->id,
                        'type' => ShippingZoneLocationType::Exclude,
                    ]);
                }
            }
        });
    }
}
