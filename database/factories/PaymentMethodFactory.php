<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $drivers = ['zarinpal', 'mellat', 'saman', 'cod', 'bank_transfer'];
        $driver = fake()->randomElement($drivers);

        $names = [
            'zarinpal' => 'درگاه پرداخت زرین‌پال',
            'mellat' => 'درگاه مستقیم بانک ملت',
            'saman' => 'درگاه امن سامان',
            'cod' => 'پرداخت درب منزل',
            'bank_transfer' => 'کارت به کارت / واریز به حساب',
        ];

        $settings = match ($driver) {
            'zarinpal' => [
                'merchant_id' => fake()->uuid(),
                'description' => 'پرداخت امن از طریق زرین‌پال',
            ],
            'mellat', 'saman' => [
                'terminal_id' => fake()->numerify('########'),
                'username' => fake()->userName(),
                'password' => fake()->password(),
            ],
            'bank_transfer' => [
                'card_number' => fake()->creditCardNumber(),
                'description' => 'لطفاً پس از واریز، فیش را در مرحله بعد آپلود کنید.',
            ],
            'cod' => [],
            default => [],
        };

        return [
            'name' => $names[$driver],
            'driver' => $driver,
            'description' => fake()->optional()->realText(),

            'settings' => $settings,

            'is_active' => fake()->boolean(80),
            'position' => fake()->numberBetween(1, 10),
        ];
    }
}
