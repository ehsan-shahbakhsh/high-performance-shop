<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/cities.json');

        if (! File::exists($path)) {
            $this->command->error("File not found at: $path");
            return;
        }

        $json = File::get($path);
        $provinces = Province::query()->pluck('id', 'name')->toArray();
        $cities = json_decode($json, true);

        foreach ($cities as $city) {
            $provinceId = $provinces[$city['province_name']];

            City::query()->updateOrCreate(
                [
                    'name' => $city['name'],
                    'province_id' => $provinceId,
                ],
                [
                    'name' => $city['name'],
                    'name_en' => $city['name_en'],
                    'slug' => $city['slug_en'],
                    'province_id' => $provinceId,
                    'latitude' => $city['latitude'],
                    'longitude' => $city['longitude'],
                ],
            );
        }
    }
}
