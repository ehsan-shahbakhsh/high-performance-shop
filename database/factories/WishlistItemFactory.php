<?php

namespace Database\Factories;

use App\Models\{Wishlist, WishlistItem, ProductVariant};
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
            'product_variant_id' => ProductVariant::factory(),
        ];
    }
}
