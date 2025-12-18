<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
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
            'balance' => fake()->numberBetween(0, 50000000),
            'is_locked' => false,
            'version' => 1,
        ];
    }

    public function empty(): static
    {
        return $this->state(fn(array $attributes) => [
            'balance' => 0,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_locked' => true,
        ]);
    }
}
