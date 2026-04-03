<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{postJson, assertDatabaseHas};
use App\Models\{User, Wishlist, Product, ProductVariant, WishlistItem};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('requires variant_id', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
    });

    it('validates variant_id exists', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", [
            'variant_id' => 999999
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
    });

    it('validates variant_id is active', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        $variant = ProductVariant::factory()->create(['is_active' => false]);

        postJson("/api/v1/wishlists/{$wishlist->id}/items", [
            'variant_id' => $variant->id
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
    });

    it('validates parent product is active', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        $variant = ProductVariant::factory()
            ->for(Product::factory()->state(['is_active' => false]))
            ->create(['is_active' => true]);

        postJson("/api/v1/wishlists/{$wishlist->id}/items", [
            'variant_id' => $variant->id
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
    });

    it('fails if the parent product status is not published', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        $variant = ProductVariant::factory()
            ->for(Product::factory()->draft())
            ->create(['is_active' => true]);

        postJson("/api/v1/wishlists/{$wishlist->id}/items", [
            'variant_id' => $variant->id
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
    });
});

describe('core logic and happy path', function () {
    it('adds variant to wishlist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create(['stock_quantity' => 1]);

        postJson("/api/v1/wishlists/{$wishlist->id}/items", ['variant_id' => $variant->id])
            ->assertCreated()
            ->assertJsonPath('message', 'محصول به لیست اضافه شد.');

        assertDatabaseHas('wishlist_items', [
            'wishlist_id' => $wishlist->id,
            'product_variant_id' => $variant->id
        ]);
    });

    it('returns created wishlist item', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create(['stock_quantity' => 1]);

        postJson("/api/v1/wishlists/{$wishlist->id}/items", ['variant_id' => $variant->id])
            ->assertCreated()
            ->assertJsonPath('data.variant.id', $variant->id);
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized when user is not authenticated', function () {
        $wishlist = Wishlist::factory()->create();
        $variant = ProductVariant::factory()->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", ['variant_id' => $variant->id])
            ->assertUnauthorized();
    });

    it('cannot add item to another users wishlist', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $wishlist = Wishlist::factory()->for($user)->create();
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create();

        Sanctum::actingAs($otherUser);

        postJson("/api/v1/wishlists/{$wishlist->id}/items", ['variant_id' => $variant->id])
            ->assertForbidden();
    });

    it('prevents adding duplicate variant to wishlist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create();

        WishlistItem::factory()->for($wishlist)->for($variant, 'variant')->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", ['variant_id' => $variant->id])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'این محصول قبلاً در این لیست وجود دارد.');
    });
});
