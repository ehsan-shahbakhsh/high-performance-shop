<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{postJson, assertDatabaseHas};
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use App\Enums\CartType;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('fails when name is missing', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        postJson('/api/v1/carts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    it('fails when name is too long', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        postJson('/api/v1/carts', [
            'name' => str_repeat('a', 300)
        ])->assertUnprocessable();
    });
});

describe('core logic and happy path', function () {
    it('creates a cart with custom name', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        postJson('/api/v1/carts', ['name' => 'My Cart'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'My Cart');

        assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'name' => 'My Cart',
            'type' => CartType::Named,
        ]);
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized for guests', function () {
        postJson('/api/v1/carts', ['name' => 'Cart'])
            ->assertUnauthorized();
    });
});
