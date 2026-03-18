<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{patchJson, assertDatabaseHas};
use App\Models\{User, Wishlist};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('requires name', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        patchJson("/api/v1/wishlists/{$wishlist->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });

    it('validates name is string', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        patchJson("/api/v1/wishlists/{$wishlist->id}", ['name' => 123])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });

    it('validates name max length', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        patchJson("/api/v1/wishlists/{$wishlist->id}", [
            'name' => str_repeat('a', 256),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });

    it('validates name is unique per user', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Wishlist::factory()->for($user)->create(['name' => 'Favorites']);

        $wishlist = Wishlist::factory()->for($user)->create(['name' => 'Other']);

        patchJson("/api/v1/wishlists/{$wishlist->id}", [
            'name' => 'Favorites'
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });
});

describe('core logic and happy path', function () {
    it('updates wishlist name', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create(['name' => 'Old Name']);

        $response = patchJson("/api/v1/wishlists/{$wishlist->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('message', 'لیست علاقه‌مندی با موفقیت بروزرسانی شد.');

        expect($response->json('data.name'))->toBe('New Name');

        assertDatabaseHas('wishlists', [
            'id' => $wishlist->id,
            'name' => 'New Name',
        ]);
    });

    it('allows same wishlist name for different users', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Wishlist::factory()->for($user1)->create(['name' => 'Favorites']);

        $wishlist = Wishlist::factory()->for($user2)->create(['name' => 'Other']);

        Sanctum::actingAs($user2);

        patchJson("/api/v1/wishlists/{$wishlist->id}", [
            'name' => 'Favorites'
        ])->assertOk();
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized when user is not authenticated', function () {
        $wishlist = Wishlist::factory()->create();

        patchJson("/api/v1/wishlists/{$wishlist->id}", [
            'name' => 'Test',
        ])->assertUnauthorized();
    });

    it('cannot update another users wishlist', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $wishlist = Wishlist::factory()->for($user)->create();

        Sanctum::actingAs($otherUser);

        patchJson("/api/v1/wishlists/{$wishlist->id}", [
            'name' => 'Hacked'
        ])->assertForbidden();
    });

    it('returns not found when wishlist does not exist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        patchJson('/api/v1/wishlists/999999', [
            'name' => 'Test'
        ])->assertNotFound();
    });

    it('cannot update the default wishlist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $defaultWishlist = $user->wishlists()
            ->where('is_default', true)
            ->first();

        patchJson("/api/v1/wishlists/{$defaultWishlist->id}", ['name' => 'Test'])
            ->assertForbidden();

        assertDatabaseHas('wishlists', [
            'id' => $defaultWishlist->id
        ]);
    });
});
