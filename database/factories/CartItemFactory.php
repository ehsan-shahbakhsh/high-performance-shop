<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),

            'product_variant_id' => ProductVariant::factory(),

            'quantity' => fake()->numberBetween(1, 10),

            'price_when_added' => fake()->numberBetween(10000, 500000),
        ];
    }

    public function quantity(int $qty): static
    {
        return $this->state(['quantity' => $qty]);
    }

    public function price(int $price): static
    {
        return $this->state(['price_when_added' => $price]);
    }
}
