<?php

namespace Database\Factories;

use App\Enums\SocialAccountProviderEnum;
use App\Models\User;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
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

            'provider' => fake()->randomElement(SocialAccountProviderEnum::cases())->value,
            'provider_id' => fake()->unique()->numerify('#####################'),

            'token' => fake()->sha256(),
            'avatar' => fake()->imageUrl(),
        ];
    }

    public function google(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => SocialAccountProviderEnum::Google,
        ]);
    }

    public function github(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => SocialAccountProviderEnum::Github,
        ]);
    }
}
