<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
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

            'product_id' => Product::factory(),
            'variant_id' => null,

            'quantity' => fake()->numberBetween(1, 3),

            'unit_price_snapshot' => fake()->numberBetween(10000, 500000),
        ];
    }

    public function withVariant(): static
    {
        return $this->state(fn() => [
            'variant_id' => ProductVariant::factory(),
        ]);
    }

    public function quantity(int $qty): static
    {
        return $this->state(fn() => [
            'quantity' => $qty,
        ]);
    }

    public function price(int $price): static
    {
        return $this->state(fn() => [
            'unit_price_snapshot' => $price,
        ]);
    }
}
