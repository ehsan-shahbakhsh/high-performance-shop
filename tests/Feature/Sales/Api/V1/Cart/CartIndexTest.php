<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{getJson, assertDatabaseHas};
use App\Models\{User, Cart};
use App\Enums\{CartType, CartStatus};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
});

describe('core logic and happy path', function () {
    it('returns authenticated user main cart', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $cart = $user->carts()->firstWhere('type', CartType::Main);

        getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
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
            ])
            ->assertJsonFragment(['id' => $cart->id]);
    });

    it('returns guest cart using session id', function () {
        $sessionId = fake()->uuid();

        $cart = Cart::factory()->create(['session_id' => $sessionId]);

        getJson('/api/v1/cart', ['Session-Id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('data.id', $cart->id);
    });

    it('returns only active cart even if others exist', function () {
        $user = User::factory()->create();

        $activeCart = $user->carts()->firstWhere('type', CartType::Main);

        Cart::factory()->for($user)->checkedOut()->create();

        Sanctum::actingAs($user);

        $response = getJson('/api/v1/cart')
            ->assertOk();

        expect($response->json('data.id'))->toBe($activeCart->id);
    });

    it('creates cart for guest if none exists', function () {
        $sessionId = fake()->uuid();

        getJson('/api/v1/cart', ['Session-Id' => $sessionId])
            ->assertOk();

        assertDatabaseHas('carts', [
            'session_id' => $sessionId,
            'type' => CartType::Main,
            'status' => CartStatus::Active,
        ]);
    });

    it('returns the requested cart type when provided', function () {
        $user = User::factory()->create();

        $secondaryCart = $user->carts()->firstWhere('type', CartType::Secondary);

        Sanctum::actingAs($user);

        getJson('/api/v1/cart?type=secondary')
            ->assertOk()
            ->assertJsonPath('data.id', $secondaryCart->id);
    });

    it('falls back to main cart if invalid type is provided', function () {
        $user = User::factory()->create();

        $mainCart = $user->carts()->firstWhere('type', CartType::Main);
        $secondaryCart = $user->carts()->firstWhere('type', CartType::Secondary);

        Sanctum::actingAs($user);

        getJson('/api/v1/cart?type=invalid-type')
            ->assertOk()
            ->assertJsonFragment(['id' => $mainCart->id])
            ->assertJsonMissing(['id' => $secondaryCart->id]);
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized when guest has no session id', function () {
        getJson('/api/v1/cart')
            ->assertUnauthorized();
    });
});
