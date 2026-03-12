<?php

namespace Database\Factories;

use App\Enums\CartStatus;
use App\Enums\CartType;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
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
            'session_id' => null,

            'type' => CartType::Main,
            'name' => null,

            'status' => CartStatus::Active,

            'meta' => null,

            'locked_at' => null,
            'lock_token' => null,

            'last_activity_at' => now(),
            'completed_at' => null,
            'abandoned_at' => null,
        ];
    }

    public function guest(): static
    {
        return $this->state(fn() => [
            'user_id' => null,
            'session_id' => fake()->uuid(),
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn() => [
            'locked_at' => now(),
            'lock_token' => Str::uuid(),
        ]);
    }

    public function checkedOut(): static
    {
        return $this->state(fn() => [
            'status' => CartStatus::CheckedOut,
            'completed_at' => now(),
        ]);
    }

    public function abandoned(): static
    {
        return $this->state(fn() => [
            'status' => CartStatus::Abandoned,
            'abandoned_at' => now(),
        ]);
    }

    public function secondary(): static
    {
        return $this->state(fn() => ['type' => CartType::Secondary]);
    }

    public function named(?string $name = null): static
    {
        return $this->state(fn() => [
            'type' => CartType::Named,
            'name' => $name ?? fake()->words(2, true),
        ]);
    }
}
