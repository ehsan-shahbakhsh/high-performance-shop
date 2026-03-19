<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{deleteJson, assertDatabaseMissing};
use App\Models\{Product, User, Cart, CartItem};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
});

describe('core logic and happy path', function () {
    it('deletes a simple cart item successfully', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;

        $product = Product::factory()->simple()->create(['manage_stock' => false]);
        $cartItem = CartItem::factory()->for($mainCart)->for($product)->create();

        deleteJson("/api/v1/cart-items/{$cartItem->id}")
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت از سبد خرید حذف شد.');

        assertDatabaseMissing('cart_items', [
            'cart_id' => $mainCart->id,
            'product_id' => $product->id,
        ]);
    });

    it('allows a guest to delete their cart item using the session id', function () {
        $sessionId = fake()->uuid();

        $cart = Cart::factory()->create(['session_id' => $sessionId]);

        $product = Product::factory()->simple()->create(['manage_stock' => false]);
        $cartItem = CartItem::factory()->for($cart)->for($product)->create();

        deleteJson("/api/v1/cart-items/{$cartItem->id}", headers: [
            'Session-Id' => $sessionId,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت از سبد خرید حذف شد.');

        assertDatabaseMissing('cart_items', [
            'cart_id' => $cart->id,
        ]);
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
            ->assertForbidden();
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

        $product = Product::factory()->simple()->create(['manage_stock' => false]);
        $cartItem = CartItem::factory()->for($mainCart)->for($product)->create();

        deleteJson("/api/v1/cart-items/{$cartItem->id}")
            ->assertForbidden();
    });
});
