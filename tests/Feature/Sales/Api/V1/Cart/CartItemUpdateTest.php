<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{patchJson, assertDatabaseHas, assertDatabaseMissing};
use App\Models\{User, Cart, CartItem, Product, ProductVariant};
use App\Enums\CartStatus;
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('requires quantity', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    });

    it('requires integer quantity', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 'foo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    });

    it('rejects negative quantity', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    });
});

describe('core logic and happy path', function () {
    it('user can update own cart item', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->create();

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

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->quantity(2)->create();

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

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->quantity(2)->create();

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

        $mainCart = $user->mainCart;
        $mainCart->update([
            'items_count' => 2,
            'items_qty_sum' => 6,
            'subtotal' => 3800,
            'total' => 3800,
        ]);

        $item1 = CartItem::factory()->for($mainCart)->quantity(2)->price(500)->create();

        CartItem::factory()->for($mainCart)->quantity(4)->price(700)->create();

        patchJson("/api/v1/cart-items/{$item1->id}", ['quantity' => 5])
            ->assertOk();

        assertDatabaseHas('cart_items', [
            'id' => $item1->id,
            'quantity' => 5,
        ]);

        $mainCart->refresh();

        expect($mainCart->items_count)->toBe(2)
            ->and($mainCart->items_qty_sum)->toBe(9)
            ->and($mainCart->subtotal)->toBe(5300)
            ->and($mainCart->total)->toBe(5300);
    });
});

describe('edge cases and errors', function () {
    it('cannot update another users cart item', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $otherUser = User::factory()->create();

        $mainCart = $otherUser->mainCart;
        $otherUserCartItem = CartItem::factory()->for($mainCart)->create();

        patchJson("/api/v1/cart-items/{$otherUserCartItem->id}", ['quantity' => 3])
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

        $mainCart = $user->mainCart;
        $mainCart->lock(fake()->uuid());
        $cartItem = CartItem::factory()->for($mainCart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 3])
            ->assertForbidden();
    });

    it('cannot update non active cart', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $mainCart->update([
            'status' => CartStatus::CheckedOut,
            'completed_at' => now(),
        ]);
        $cartItem = CartItem::factory()->for($mainCart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 3])
            ->assertForbidden();
    });

    it('cannot exceed stock', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;

        $product = Product::factory()->create(['manage_stock' => true]);
        $variant = ProductVariant::factory()->for($product)->create(['stock_quantity' => 5]);
        $cartItem = CartItem::factory()->for($mainCart)->for($product)->for($variant, 'variant')->create();

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
