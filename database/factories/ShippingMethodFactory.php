<?php

namespace Database\Factories;

use App\Models\ShippingCarrier;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    protected array $methodNames = [
        'پست پیشتاز',
        'پست سفارشی',
        'تیپاکس زمینی',
        'تیپاکس هوایی',
        'پیک موتوری (فوری)',
        'پیک بادپا',
        'باربری وطن',
        'ارسال با اتوبوس',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement($this->methodNames);
        $uniqueSuffix = fake()->unique()->numberBetween(100, 99999);
        $slug = Str::slug($name) . '_' . $uniqueSuffix;

        $minTime = fake()->numberBetween(120, 4320);
        $maxTime = $minTime + fake()->numberBetween(300, 2880);

        return [
            'carrier_id' => ShippingCarrier::factory(),

            'name' => $name,
            'code' => $slug,
            'description' => fake()->realText(100),

            'min_delivery_time' => $minTime,
            'max_delivery_time' => $maxTime,

            'is_cod_supported' => fake()->boolean(40),
            'max_weight' => fake()->randomElement([2000, 5000, 10000, 30000, null]),

            'settings' => [
                'insurance_rate' => 0.5,
                'fragile_handling' => fake()->boolean(),
                'require_signature' => true,
            ],

            'is_active' => fake()->boolean(90),
            'position' => fake()->numberBetween(0, 100),
        ];
    }

    public function pishtaz(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'پست پیشتاز',
            'code' => 'post_pishtaz_' . fake()->unique()->numberBetween(1, 999),
            'min_delivery_time' => 2880,
            'max_delivery_time' => 5760,
            'is_cod_supported' => true,
            'max_weight' => 30000,
        ]);
    }

    public function bike(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'پیک موتوری ویژه',
            'code' => 'motor_express_' . fake()->unique()->numberBetween(1, 999),
            'min_delivery_time' => 30,
            'max_delivery_time' => 120,
            'is_cod_supported' => false,
            'max_weight' => 5000,
        ]);
    }
}
