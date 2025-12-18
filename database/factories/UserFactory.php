<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),

            'mobile' => '09' . fake()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),

            'mobile_verified_at' => now(),
            'email_verified_at' => now(),

            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),

            'is_admin' => false,
            'banned_at' => null,

            'avatar' => 'https://ui-avatars.com/api/?name=' . fake()->firstName,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
            'mobile_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_admin' => true,
        ]);
    }

    public function banned(): static
    {
        return $this->state(fn(array $attributes) => [
            'banned_at' => now(),
        ]);
    }

    public function mobileOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => null,
            'email_verified_at' => null,
        ]);
    }
}
