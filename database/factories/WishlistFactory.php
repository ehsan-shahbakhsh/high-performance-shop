<?php

namespace Database\Factories;

use App\Models\{User, Wishlist};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wishlist>
 */
class WishlistFactory extends Factory
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
            'name' => fake()->word(),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state([
            'is_default' => true,
            'name' => 'علاقه‌مندی‌ها',
        ]);
    }
}
