<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provinces = [
            [
                'name' => 'آذربایجان شرقی',
                'name_en' => 'East Azerbaijan',
                'slug' => 'east-azerbaijan',
                'tel_prefix' => '041',
                'latitude' => 38.0962,
                'longitude' => 46.2738,
            ],
            [
                'name' => 'آذربایجان غربی',
                'name_en' => 'West Azerbaijan',
                'slug' => 'west-azerbaijan',
                'tel_prefix' => '044',
                'latitude' => 37.5527,
                'longitude' => 45.0761,
            ],
            [
                'name' => 'اردبیل',
                'name_en' => 'Ardabil',
                'slug' => 'ardabil',
                'tel_prefix' => '045',
                'latitude' => 38.2498,
                'longitude' => 48.2933,
            ],
            [
                'name' => 'اصفهان',
                'name_en' => 'Isfahan',
                'slug' => 'isfahan',
                'tel_prefix' => '031',
                'latitude' => 32.6546,
                'longitude' => 51.6680,
            ],
            [
                'name' => 'البرز',
                'name_en' => 'Alborz',
                'slug' => 'alborz',
                'tel_prefix' => '026',
                'latitude' => 35.8327,
                'longitude' => 50.9915,
            ],
            [
                'name' => 'ایلام',
                'name_en' => 'Ilam',
                'slug' => 'ilam',
                'tel_prefix' => '084',
                'latitude' => 33.6385,
                'longitude' => 46.4226,
            ],
            [
                'name' => 'بوشهر',
                'name_en' => 'Bushehr',
                'slug' => 'bushehr',
                'tel_prefix' => '077',
                'latitude' => 28.9234,
                'longitude' => 50.8203,
            ],
            [
                'name' => 'تهران',
                'name_en' => 'Tehran',
                'slug' => 'tehran',
                'tel_prefix' => '021',
                'latitude' => 35.6892,
                'longitude' => 51.3890,
            ],
            [
                'name' => 'چهارمحال و بختیاری',
                'name_en' => 'Chahar Mahaal and Bakhtiari',
                'slug' => 'chahar-mahaal-and-bakhtiari',
                'tel_prefix' => '038',
                'latitude' => 32.3256,
                'longitude' => 50.8644,
            ],
            [
                'name' => 'خراسان جنوبی',
                'name_en' => 'South Khorasan',
                'slug' => 'south-khorasan',
                'tel_prefix' => '056',
                'latitude' => 32.8663,
                'longitude' => 59.2211,
            ],
            [
                'name' => 'خراسان رضوی',
                'name_en' => 'Razavi Khorasan',
                'slug' => 'razavi-khorasan',
                'tel_prefix' => '051',
                'latitude' => 36.2972,
                'longitude' => 59.6067,
            ],
            [
                'name' => 'خراسان شمالی',
                'name_en' => 'North Khorasan',
                'slug' => 'north-khorasan',
                'tel_prefix' => '058',
                'latitude' => 37.4747,
                'longitude' => 57.3290,
            ],
            [
                'name' => 'خوزستان',
                'name_en' => 'Khuzestan',
                'slug' => 'khuzestan',
                'tel_prefix' => '061',
                'latitude' => 31.3183,
                'longitude' => 48.6706,
            ],
            [
                'name' => 'زنجان',
                'name_en' => 'Zanjan',
                'slug' => 'zanjan',
                'tel_prefix' => '024',
                'latitude' => 36.6736,
                'longitude' => 48.4787,
            ],
            [
                'name' => 'سمنان',
                'name_en' => 'Semnan',
                'slug' => 'semnan',
                'tel_prefix' => '023',
                'latitude' => 35.5769,
                'longitude' => 53.3971,
            ],
            [
                'name' => 'سیستان و بلوچستان',
                'name_en' => 'Sistan and Baluchestan',
                'slug' => 'sistan-and-baluchestan',
                'tel_prefix' => '054',
                'latitude' => 29.4124,
                'longitude' => 60.8621,
            ],
            [
                'name' => 'فارس',
                'name_en' => 'Fars',
                'slug' => 'fars',
                'tel_prefix' => '071',
                'latitude' => 29.5918,
                'longitude' => 52.5837,
            ],
            [
                'name' => 'قزوین',
                'name_en' => 'Qazvin',
                'slug' => 'qazvin',
                'tel_prefix' => '028',
                'latitude' => 36.2688,
                'longitude' => 50.0041,
            ],
            [
                'name' => 'قم',
                'name_en' => 'Qom',
                'slug' => 'qom',
                'tel_prefix' => '025',
                'latitude' => 34.6399,
                'longitude' => 50.8759,
            ],
            [
                'name' => 'کردستان',
                'name_en' => 'Kurdistan',
                'slug' => 'kurdistan',
                'tel_prefix' => '087',
                'latitude' => 35.3219,
                'longitude' => 46.9862,
            ],
            [
                'name' => 'کرمان',
                'name_en' => 'Kerman',
                'slug' => 'kerman',
                'tel_prefix' => '034',
                'latitude' => 30.2839,
                'longitude' => 57.0834,
            ],
            [
                'name' => 'کرمانشاه',
                'name_en' => 'Kermanshah',
                'slug' => 'kermanshah',
                'tel_prefix' => '083',
                'latitude' => 34.3277,
                'longitude' => 47.0778,
            ],
            [
                'name' => 'کهگیلویه و بویراحمد',
                'name_en' => 'Kohgiluyeh and Boyer-Ahmad',
                'slug' => 'kohgiluyeh-and-boyer-ahmad',
                'tel_prefix' => '074',
                'latitude' => 30.6690,
                'longitude' => 51.5876,
            ],
            [
                'name' => 'گلستان',
                'name_en' => 'Golestan',
                'slug' => 'golestan',
                'tel_prefix' => '017',
                'latitude' => 36.8425,
                'longitude' => 54.4324,
            ],
            [
                'name' => 'گیلان',
                'name_en' => 'Gilan',
                'slug' => 'gilan',
                'tel_prefix' => '013',
                'latitude' => 37.2808,
                'longitude' => 49.5831,
            ],
            [
                'name' => 'لرستان',
                'name_en' => 'Lorestan',
                'slug' => 'lorestan',
                'tel_prefix' => '066',
                'latitude' => 33.4871,
                'longitude' => 48.3538,
            ],
            [
                'name' => 'مازندران',
                'name_en' => 'Mazandaran',
                'slug' => 'mazandaran',
                'tel_prefix' => '011',
                'latitude' => 36.5633,
                'longitude' => 53.0601,
            ],
            [
                'name' => 'مرکزی',
                'name_en' => 'Markazi',
                'slug' => 'markazi',
                'tel_prefix' => '086',
                'latitude' => 34.0954,
                'longitude' => 49.7013,
            ],
            [
                'name' => 'هرمزگان',
                'name_en' => 'Hormozgan',
                'slug' => 'hormozgan',
                'tel_prefix' => '076',
                'latitude' => 27.1711,
                'longitude' => 56.2736,
            ],
            [
                'name' => 'همدان',
                'name_en' => 'Hamedan',
                'slug' => 'hamedan',
                'tel_prefix' => '081',
                'latitude' => 34.7981,
                'longitude' => 48.5146,
            ],
            [
                'name' => 'یزد',
                'name_en' => 'Yazd',
                'slug' => 'yazd',
                'tel_prefix' => '035',
                'latitude' => 31.8974,
                'longitude' => 54.3569,
            ],
        ];

        foreach ($provinces as $index => $data) {
            Province::query()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'name_en' => $data['name_en'],
                    'slug' => $data['slug'],
                    'tel_prefix' => $data['tel_prefix'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'is_active' => true,
                    'position' => $index + 1,
                ]
            );
        }
    }
}
