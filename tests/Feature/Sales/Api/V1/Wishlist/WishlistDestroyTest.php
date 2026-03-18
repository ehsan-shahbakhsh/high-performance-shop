<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{deleteJson, assertDatabaseHas, assertDatabaseMissing};
use App\Models\{User, Wishlist};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
});

describe('core logic and happy path', function () {
    it('deletes a wishlist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        deleteJson("/api/v1/wishlists/{$wishlist->id}")
            ->assertOk()
            ->assertJsonPath('message', 'لیست علاقه‌مندی حذف شد.');

        assertDatabaseMissing('wishlists', [
            'id' => $wishlist->id
        ]);
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized if user is not authenticated', function () {
        $wishlist = Wishlist::factory()->create();

        deleteJson("/api/v1/wishlists/{$wishlist->id}")
            ->assertUnauthorized();
    });

    it('cannot delete another users wishlist', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $wishlist = Wishlist::factory()->for($user)->create();

        Sanctum::actingAs($otherUser);

        deleteJson("/api/v1/wishlists/{$wishlist->id}")
            ->assertForbidden();
    });

    it('returns not found when wishlist does not exist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        deleteJson('/api/v1/wishlists/999999')
            ->assertNotFound();
    });

    it('cannot delete the default wishlist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $defaultWishlist = $user->wishlists()
            ->where('is_default', true)
            ->first();

        deleteJson("/api/v1/wishlists/{$defaultWishlist->id}")
            ->assertForbidden();

        assertDatabaseHas('wishlists', [
            'id' => $defaultWishlist->id
        ]);
    });
});
