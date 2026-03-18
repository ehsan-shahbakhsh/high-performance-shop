<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{deleteJson, assertDatabaseHas, assertDatabaseMissing};
use App\Models\{User, Wishlist, WishlistItem};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {});

describe('core logic and happy path', function () {
    it('deletes item from wishlist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        $item = WishlistItem::factory()->for($wishlist)->create();

        deleteJson("/api/v1/wishlists/{$wishlist->id}/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('message', 'محصول از لیست حذف شد.');

        assertDatabaseMissing('wishlist_items', [
            'id' => $item->id
        ]);
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized when user is not authenticated', function () {
        $wishlist = Wishlist::factory()->create();
        $item = WishlistItem::factory()->for($wishlist)->create();

        deleteJson("/api/v1/wishlists/{$wishlist->id}/items/{$item->id}")
            ->assertUnauthorized();
    });

    it('cannot delete item from another users wishlist', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $wishlist = Wishlist::factory()->for($user)->create();

        $item = WishlistItem::factory()->for($wishlist)->create();

        Sanctum::actingAs($otherUser);

        deleteJson("/api/v1/wishlists/{$wishlist->id}/items/{$item->id}")
            ->assertForbidden();
    });

    it('returns not found when item does not exist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        deleteJson("/api/v1/wishlists/{$wishlist->id}/items/999999")
            ->assertNotFound();
    });

    it('returns not found when item does not belong to the wishlist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();
        $otherWishlist = Wishlist::factory()->for($user)->create();

        $item = WishlistItem::factory()->for($otherWishlist)->create();

        deleteJson("/api/v1/wishlists/{$wishlist->id}/items/{$item->id}")
            ->assertNotFound();
    });
});
