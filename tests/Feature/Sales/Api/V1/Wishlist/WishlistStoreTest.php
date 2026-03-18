<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{postJson, assertDatabaseHas};
use App\Models\{User, Wishlist};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('requires name', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        postJson('/api/v1/wishlists', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });

    it('validates name is string', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        postJson('/api/v1/wishlists', ['name' => 123])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });

    it('validates name max length', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        postJson('/api/v1/wishlists', [
            'name' => str_repeat('a', 256),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });

    it('validates name is unique per user', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Wishlist::factory()->for($user)->create(['name' => 'Favorites']);

        postJson('/api/v1/wishlists', ['name' => 'Favorites'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    });
});

describe('core logic and happy path', function () {
    it('creates a wishlist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = postJson('/api/v1/wishlists', ['name' => 'My Wishlist'])
            ->assertCreated()
            ->assertJsonPath('message', 'لیست علاقه‌مندی با موفقیت ایجاد شد.');

        expect($response->json('data.name'))->toBe('My Wishlist');

        assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'name' => 'My Wishlist',
            'is_default' => null,
        ]);
    });

    it('allows same wishlist name for different users', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Wishlist::factory()->for($user1)->create(['name' => 'Favorites']);

        Sanctum::actingAs($user2);

        postJson('/api/v1/wishlists', ['name' => 'Favorites'])
            ->assertCreated();
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized when user is not authenticated', function () {
        postJson('/api/v1/wishlists', [
            'name' => 'Test',
        ])->assertUnauthorized();
    });

    it('cannot create more than allowed wishlists', function () {
        $user = User::factory()
            ->has(
                Wishlist::factory()
                    ->count(20)
                    ->sequence(fn($sequence) => [
                        'name' => 'Wishlist ' . $sequence->index,
                    ])
            )->create();

        Sanctum::actingAs($user);

        postJson('/api/v1/wishlists', [
            'name' => 'Another',
        ])->assertUnprocessable();
    });
});
