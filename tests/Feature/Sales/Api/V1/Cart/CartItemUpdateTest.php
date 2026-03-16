<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{patchJson, assertDatabaseHas, assertDatabaseMissing};
use App\Models\{User, Cart, CartItem, Product, ProductVariant};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {

    it('requires quantity', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    });

    it('requires integer quantity', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 'foo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    });

    it('rejects negative quantity', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    });

});

describe('core logic and happy path', function () {

    it('user can update own cart item', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('message', 'تعداد محصول در سبد خرید بروزرسانی شد.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
        ]);
    });

    it('removes cart item when quantity is zero', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->quantity(2)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 0])
            ->assertOk()
            ->assertJsonPath('message', 'محصول از سبد خرید حذف شد.');

        assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id
        ]);
    });

    it('guest can update own cart item', function () {
        $sessionId = fake()->uuid();

        $cart = Cart::factory()->create(['session_id' => $sessionId]);
        $cartItem = CartItem::factory()->for($cart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 3], ['Session-Id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('message', 'تعداد محصول در سبد خرید بروزرسانی شد.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
        ]);
    });

    it('updates quantity correctly', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->quantity(2)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 5])
            ->assertOk()
            ->assertJsonPath('message', 'تعداد محصول در سبد خرید بروزرسانی شد.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('message', 'تعداد محصول در سبد خرید بروزرسانی شد.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 3,
        ]);
    });

    it('recalculates cart totals', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = Cart::factory()->for($user)->create([
            'items_count' => 2,
            'items_qty_sum' => 6,
            'subtotal' => 3800,
            'total' => 3800,
        ]);

        $item1 = CartItem::factory()->for($cart)->quantity(2)->price(500)->create();

        CartItem::factory()->for($cart)->quantity(4)->price(700)->create();

        patchJson("/api/v1/cart-items/{$item1->id}", ['quantity' => 5])
            ->assertOk();

        assertDatabaseHas('cart_items', [
            'id' => $item1->id,
            'quantity' => 5,
        ]);

        $cart->refresh();

        expect($cart->items_count)->toBe(2)
            ->and($cart->items_qty_sum)->toBe(9)
            ->and($cart->subtotal)->toBe(5300)
            ->and($cart->total)->toBe(5300);
    });

});

describe('edge cases and errors', function () {

    it('cannot update another users cart item', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $anotherUser = User::factory()->create();

        $cart = Cart::factory()->for($anotherUser)->create();
        $anotherUserCartItem = CartItem::factory()->for($cart)->create();

        patchJson("/api/v1/cart-items/{$anotherUserCartItem->id}", ['quantity' => 3])
            ->assertForbidden();
    });

    it('guest cannot update other session cart', function () {
        $sessionId = fake()->uuid();
        $anotherSessionId = fake()->uuid();

        $cart = Cart::factory()->create(['session_id' => $anotherSessionId]);
        $anotherUserCartItem = CartItem::factory()->for($cart)->create();

        patchJson("/api/v1/cart-items/{$anotherUserCartItem->id}", ['quantity' => 3], ['Session-Id' => $sessionId])
            ->assertForbidden();
    });

    it('cannot update locked cart', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = Cart::factory()->locked()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 3])
            ->assertForbidden();
    });

    it('cannot update non active cart', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = Cart::factory()->checkedOut()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 3])
            ->assertForbidden();
    });

    it('cannot exceed stock', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = Cart::factory()->for($user)->create();

        $product = Product::factory()->create(['manage_stock' => true]);
        $variant = ProductVariant::factory()->for($product)->create(['stock_quantity' => 5]);
        $cartItem = CartItem::factory()->for($cart)->for($product)->for($variant, 'variant')->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 8])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'موجودی کافی نیست یا کالا در سبد دیگران رزرو شده است.');
    });

    it('returns 404 for missing item', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        patchJson('/api/v1/cart-items/999', ['quantity' => 3])
            ->assertNotFound();
    });

});
