<?php

namespace Database\Factories;

use App\Enums\WalletTransactionTypeEnum;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'uuid' => fake()->uuid(),

            'type' => fake()->randomElement(WalletTransactionTypeEnum::cases())->value,

            'amount' => fake()->numberBetween(10000, 5000000),

            'balance_before' => fake()->numberBetween(10000, 5000000),
            'balance_after' => fake()->numberBetween(10000, 5000000),

            'confirmed' => true,

            'meta' => [
                'ip' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'description' => fake()->sentence(),
                'reference_id' => Str::random(10),
            ],

            'related_id' => null,
            'related_type' => null,
        ];
    }

    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WalletTransactionTypeEnum::Deposit,
        ]);
    }

    public function withdraw(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WalletTransactionTypeEnum::Withdraw,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'confirmed' => false,
        ]);
    }

    public function related($model): static
    {
        return $this->state(fn (array $attributes) => [
            'related_type' => get_class($model),
            'related_id' => $model->id,
        ]);
    }
}
