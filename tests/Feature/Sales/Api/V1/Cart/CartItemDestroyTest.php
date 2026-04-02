<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{deleteJson, assertDatabaseMissing, assertDatabaseHas};
use App\Models\{ProductVariant, User, CartItem};
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\Redis;

uses(TestCase::class, RefreshDatabase::class);

describe('core logic and happy path', function () {
    it('deletes a simple cart item successfully', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;

        $variant = ProductVariant::factory()->create();
        $cartItem = CartItem::factory()->for($mainCart)->for($variant, 'variant')->create();

        deleteJson("/api/v1/cart-items/{$cartItem->id}")
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت از سبد خرید حذف شد.');

        assertDatabaseMissing('cart_items', [
            'cart_id' => $mainCart->id,
            'product_variant_id' => $variant->id,
        ]);
    });

    it('allows a guest to delete their cart item using the session id', function () {
        $sessionId = fake()->uuid();

        $variant = ProductVariant::factory()->create();

        Redis::setex("cart:$sessionId", 14 * 86400, json_encode([
            $variant->id => [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                'price_when_added' => $variant->final_price,
            ],
        ]));

        deleteJson("/api/v1/cart-items/{$variant->id}", headers: [
            'Session-Id' => $sessionId,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت از سبد خرید حذف شد.');

        $items = json_decode(Redis::get("cart:$sessionId"), true);
        expect($items)->not->toHaveKey($variant->id);
    });

    it('deletes the cart item and recalculates cart totals correctly', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $mainCart->update([
            'items_count' => 2,
            'items_qty_sum' => 3,
            'subtotal' => 1700,
            'total' => 1700,
        ]);

        $item1 = CartItem::factory()->for($mainCart)->quantity(2)->price(500)->create();

        CartItem::factory()->for($mainCart)->quantity(1)->price(700)->create();

        deleteJson("/api/v1/cart-items/{$item1->id}")
            ->assertOk();

        assertDatabaseMissing('cart_items', [
            'id' => $item1->id,
        ]);

        $mainCart->refresh();

        expect($mainCart->items_count)->toBe(1)
            ->and($mainCart->items_qty_sum)->toBe(1)
            ->and($mainCart->subtotal)->toBe(700)
            ->and($mainCart->total)->toBe(700);
    });
});

describe('edge cases and errors', function () {
    it('does not allow guest deletion without session id', function () {
        $cartItem = CartItem::factory()->create();

        deleteJson("/api/v1/cart-items/{$cartItem->id}")
            ->assertUnauthorized();
    });

    it('fails when cart item does not exist', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        deleteJson('/api/v1/cart-items/999999')
            ->assertNotFound();
    });

    it('cannot delete a cart item when the cart is locked', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $mainCart->lock(fake()->uuid());

        $cartItem = CartItem::factory()->for($mainCart)->create();

        deleteJson("/api/v1/cart-items/{$cartItem->id}")
            ->assertForbidden();
    });

    it('prevents a user from deleting another users cart item', function () {
        $victim = User::factory()->create();
        $victimItem = CartItem::factory()->for($victim->mainCart)->create();

        $hacker = User::factory()->create();
        Sanctum::actingAs($hacker);

        deleteJson("/api/v1/cart-items/{$victimItem->id}")
            ->assertForbidden();

        assertDatabaseHas('cart_items', [
            'id' => $victimItem->id,
        ]);
    });

    it('fails when a guest tries to delete a non-existent item from their cart', function () {
        $sessionId = fake()->uuid();

        $existingVariant = ProductVariant::factory()->create();

        $nonExistentVariantId = 999999;

        Redis::setex("cart:$sessionId", 14 * 86400, json_encode([
            $existingVariant->id => [
                'product_variant_id' => $existingVariant->id,
                'quantity' => 1,
                'price_when_added' => 5000,
            ],
        ]));

        deleteJson("/api/v1/cart-items/{$nonExistentVariantId}", headers: [
            'Session-Id' => $sessionId,
        ])
            ->assertNotFound()
            ->assertJsonPath('message', 'آیتم در سبد خرید یافت نشد.');

        $items = json_decode(Redis::get("cart:$sessionId"), true);
        expect($items)->toHaveKey($existingVariant->id)
            ->and($items)->not->toHaveKey($nonExistentVariantId);
    });
});
