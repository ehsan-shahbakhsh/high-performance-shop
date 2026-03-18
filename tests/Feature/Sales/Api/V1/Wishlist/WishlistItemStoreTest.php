<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{postJson, assertDatabaseHas};
use App\Models\{User, Wishlist, Product, ProductVariant, WishlistItem};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('requires product_id', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');
    });

    it('validates product exists', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", [
            'product_id' => 999999
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');
    });

    it('validates variant exists', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();
        $product = Product::factory()->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", [
            'product_id' => $product->id,
            'variant_id' => 999999
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
    });

    it('validates that the variant belongs to the given product', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();

        $variant = ProductVariant::factory()->for($otherProduct)->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variant_id']);
    });
});

describe('core logic and happy path', function () {
    it('adds product to wishlist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();
        $product = Product::factory()->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", ['product_id' => $product->id])
            ->assertCreated()
            ->assertJsonPath('message', 'محصول به لیست اضافه شد.');

        assertDatabaseHas('wishlist_items', [
            'wishlist_id' => $wishlist->id,
            'product_id' => $product->id
        ]);
    });

    it('returns created wishlist item', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();
        $product = Product::factory()->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", ['product_id' => $product->id])
            ->assertCreated()
            ->assertJsonPath('data.product.id', $product->id);
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized when user is not authenticated', function () {
        $wishlist = Wishlist::factory()->create();
        $product = Product::factory()->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", ['product_id' => $product->id])
            ->assertUnauthorized();
    });

    it('cannot add item to another users wishlist', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $wishlist = Wishlist::factory()->for($user)->create();
        $product = Product::factory()->create();

        Sanctum::actingAs($otherUser);

        postJson("/api/v1/wishlists/{$wishlist->id}/items", ['product_id' => $product->id])
            ->assertForbidden();
    });

    it('prevents adding duplicate product to wishlist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();
        $product = Product::factory()->create();

        WishlistItem::factory()->for($wishlist)->for($product)->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", ['product_id' => $product->id])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'این محصول قبلاً در این لیست وجود دارد.');
    });
});
