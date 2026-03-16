<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\getJson;
use App\Models\{User, Cart};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
});

describe('core logic and happy path', function () {
    it('returns user carts', function () {
        $user = User::factory()
            ->has(Cart::factory())
            ->create();

        Sanctum::actingAs($user);

        getJson('/api/v1/carts')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'status',

                        'financials' => [
                            'subtotal',
                            'discount_total',
                            'shipping_total',
                            'total',
                        ],

                        'summary' => [
                            'items_count',
                            'total_quantity',
                        ],

                        'is_locked',

                        'timestamps' => [
                            'last_activity_at',
                            'created_at',
                        ],

                        'meta',
                    ],
                ],
            ]);
    });

    it('returns only authenticated user carts', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userCart = Cart::factory()->create([
            'user_id' => $user->id,
        ]);
        $otherUserCart = Cart::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($user);

        $response = getJson('/api/v1/carts')
            ->assertOk()
            ->assertJsonFragment(['id' => $userCart->id])
            ->assertJsonMissing(['id' => $otherUserCart->id]);

        expect($response->json('data'))->toHaveCount(1);
    });

    it('returns empty list when user has no carts', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        getJson('/api/v1/carts')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized when user is not authenticated', function () {
        getJson('/api/v1/carts')
            ->assertUnauthorized();
    });
});
