<?php

namespace Database\Factories;

use App\Models\ShippingCarrier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingCarrier>
 */
class ShippingCarrierFactory extends Factory
{
    protected array $carriers = [
        ['name' => 'شرکت ملی پست', 'code' => 'post', 'url' => 'https://tracking.post.ir/?id={tracking_code}'],
        ['name' => 'تیپاکس', 'code' => 'tipax', 'url' => 'https://tipaxco.com/tracking?id={tracking_code}'],
        ['name' => 'الوپیک', 'code' => 'alopeyk', 'url' => 'https://alopeyk.com/tracking/{tracking_code}'],
        ['name' => 'ماهکس', 'code' => 'mahex', 'url' => 'https://mahex.com/track/{tracking_code}'],
        ['name' => 'اسنپ باکس', 'code' => 'snapp_box', 'url' => 'https://snapp.ir/box/tracking/{tracking_code}'],
        ['name' => 'چاپار', 'code' => 'chapar', 'url' => 'https://chapar.com/track/{tracking_code}'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $carrier = fake()->randomElement($this->carriers);

        $uniqueSuffix = fake()->unique()->numberBetween(1, 9999);

        return [
            'name' => $carrier['name'],
            'code' => $carrier['code'] . '_' . $uniqueSuffix,
            'is_active' => fake()->boolean(90),
            'tracking_url_template' => $carrier['url'],
            'logo_path' => null,
            'position' => fake()->numberBetween(0, 100),

            'settings' => [
                'api_key' => fake()->uuid(),
                'merchant_id' => fake()->numerify('mer_######'),
                'service_type' => fake()->randomElement(['standard', 'express', 'rocket']),
                'timeout' => 30,
            ],
        ];
    }

    public function post(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'شرکت ملی پست',
            'code' => 'post',
            'tracking_url_template' => 'https://tracking.post.ir/?id={tracking_code}',
            'position' => 1,
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
