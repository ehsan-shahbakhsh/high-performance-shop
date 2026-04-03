<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\getJson;
use App\Models\{User, Wishlist, WishlistItem};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
});

describe('core logic and happy path', function () {
    it('returns wishlist items', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()
            ->for($user)
            ->has(WishlistItem::factory()->count(3), 'items')
            ->create();

        getJson("/api/v1/wishlists/{$wishlist->id}/items")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('loads product and variant for each wishlist item', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()
            ->has(WishlistItem::factory()->count(3), 'items')
            ->for($user)
            ->create();

        getJson("/api/v1/wishlists/{$wishlist->id}/items")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product' => [
                            'id',
                        ],
                        'variant' => [
                            'id',
                        ],
                    ]
                ]
            ]);
    });

    it('paginates wishlist items', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()
            ->for($user)
            ->has(WishlistItem::factory()->count(25), 'items')
            ->create();

        getJson("/api/v1/wishlists/{$wishlist->id}/items")
            ->assertOk()
            ->assertJsonCount(20, 'data');
    });

    it('returns only items belonging to the wishlist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();
        $otherWishlist = Wishlist::factory()->for($user)->create();

        WishlistItem::factory()->count(3)->for($wishlist)->create();
        WishlistItem::factory()->count(5)->for($otherWishlist)->create();

        getJson("/api/v1/wishlists/{$wishlist->id}/items")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    });

    it('returns items ordered by latest first', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

         WishlistItem::factory()->for($wishlist)->create(['created_at' => now()->subDay()]);

        $new = WishlistItem::factory()->for($wishlist)->create(['created_at' => now()]);

        getJson("/api/v1/wishlists/{$wishlist->id}/items")
            ->assertOk()
            ->assertJsonPath('data.0.id', $new->id);
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized when user is not authenticated', function () {
        $wishlist = Wishlist::factory()->create();

        getJson("/api/v1/wishlists/{$wishlist->id}/items")
            ->assertUnauthorized();
    });

    it('returns not found when wishlist does not exist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        getJson('/api/v1/wishlists/999999/items')
            ->assertNotFound();
    });

    it('cannot view another users wishlist items', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $wishlist = Wishlist::factory()
            ->for($user)
            ->has(WishlistItem::factory()->count(3), 'items')
            ->create();

        Sanctum::actingAs($otherUser);

        getJson("/api/v1/wishlists/{$wishlist->id}/items")
            ->assertForbidden();
    });
});
