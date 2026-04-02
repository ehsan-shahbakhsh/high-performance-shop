<?php

namespace Database\Factories;

use App\Enums\{CartStatus, CartType};
use App\Models\{Cart, User};
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

            'type' => CartType::Main,

            'status' => CartStatus::Active,

            'meta' => null,

            'locked_at' => null,
            'lock_token' => null,

            'last_activity_at' => now(),
            'completed_at' => null,
            'abandoned_at' => null,
        ];
    }

    public function locked(): static
    {
        return $this->state([
            'locked_at' => now(),
            'lock_token' => Str::uuid(),
        ]);
    }

    public function checkedOut(): static
    {
        return $this->state([
            'status' => CartStatus::CheckedOut,
            'completed_at' => now(),
        ]);
    }

    public function abandoned(): static
    {
        return $this->state([
            'status' => CartStatus::Abandoned,
            'abandoned_at' => now(),
        ]);
    }

    public function secondary(): static
    {
        return $this->state(['type' => CartType::Secondary]);
    }
}
