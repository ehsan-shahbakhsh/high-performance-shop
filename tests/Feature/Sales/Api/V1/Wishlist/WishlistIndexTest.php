<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\getJson;
use App\Models\{User, Wishlist};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
});

describe('core logic and happy path', function () {
    it('returns user wishlists', function () {
        $user = User::factory()
            ->has(Wishlist::factory())
            ->create();

        Sanctum::actingAs($user);

        getJson('/api/v1/wishlists')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'is_default',
                        'items_count',
                    ],
                ],
            ]);
    });

    it('returns only authenticated user wishlists', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userWishlist = Wishlist::factory()->for($user)->create();
        $otherUserWishlist = Wishlist::factory()->for($otherUser)->create();

        Sanctum::actingAs($user);

        getJson('/api/v1/wishlists')
            ->assertOk()
            ->assertJsonFragment(['id' => $userWishlist->id])
            ->assertJsonMissing(['id' => $otherUserWishlist->id])
            ->assertJsonCount(2, 'data');
    });

    it('returns the default wishlist for new user', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        getJson('/api/v1/wishlists')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_default', true);
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized when user is not authenticated', function () {
        getJson('/api/v1/wishlists')
            ->assertUnauthorized();
    });
});
