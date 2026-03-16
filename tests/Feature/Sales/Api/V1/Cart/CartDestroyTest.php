<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{deleteJson, assertDatabaseMissing};
use App\Models\{User, Cart};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('returns 404 when cart does not exist', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        deleteJson('/api/v1/carts/999')
            ->assertNotFound();
    });
});

describe('core logic and happy path', function () {
    it('deletes the cart', function () {
        $user = User::factory()->create();

        $cart = Cart::factory()->named()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        deleteJson("/api/v1/carts/{$cart->id}")
            ->assertOk()
            ->assertJsonPath('message', 'سبد خرید و تمامی محصولات آن حذف شدند.');

        assertDatabaseMissing('carts', ['id' => $cart->id]);
    });
});

describe('edge cases and errors', function () {
    it('prevents deleting carts of other users', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $cart = Cart::factory()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($user);

        deleteJson("/api/v1/carts/{$cart->id}")
            ->assertForbidden();
    });

    it('cannot delete main cart', function () {
        $user = User::factory()->create();

        $cart = Cart::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        deleteJson("/api/v1/carts/{$cart->id}")
            ->assertForbidden();
    });

    it('cannot delete secondary cart', function () {
        $user = User::factory()->create();

        $cart = Cart::factory()->secondary()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        deleteJson("/api/v1/carts/{$cart->id}")
            ->assertForbidden();
    });
});
