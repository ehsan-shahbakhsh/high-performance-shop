<?php

namespace Database\Factories;

use App\Models\{Product, ProductVariant};
use App\Models\{Wishlist, WishlistItem};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wishlist_id' => Wishlist::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
        ];
    }

    public function withVariant(?ProductVariant $variant = null): static
    {
        return $this->state(function () use ($variant) {
            $variant ??= ProductVariant::factory()->create();

            return [
                'variant_id' => $variant->id,
                'product_id' => $variant->product_id,
            ];
        });
    }
}
