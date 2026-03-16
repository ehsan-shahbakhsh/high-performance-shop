<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{postJson, assertDatabaseHas, assertDatabaseMissing};
use App\Models\{Product, User, Cart, CartItem};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('fails when destination_cart_id is missing', function () {
        $user = User::factory()->create();

        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/cart-items/{$cartItem->id}/move")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('destination_cart_id');
    });

    it('fails when destination_cart_id is not a valid ulid', function () {
        $user = User::factory()->create();

        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/cart-items/{$cartItem->id}/move", ['destination_cart_id' => 'foo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('destination_cart_id');
    });

    it('fails when destination_cart_id does not exist', function () {
        $user = User::factory()->create();

        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/cart-items/{$cartItem->id}/move", ['destination_cart_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('destination_cart_id');
    });

    it('fails when destination_cart_id belongs to another user', function () {
        $user = User::factory()->create();

        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->create();

        Sanctum::actingAs($user);

        $anotherUser = User::factory()->create();
        $anotherUserCart = Cart::factory()->for($anotherUser)->create();

        postJson("/api/v1/cart-items/{$cartItem->id}/move", ['destination_cart_id' => $anotherUserCart->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('destination_cart_id');
    });

    it('fails when destination_cart_id points to an inactive cart', function () {
        $user = User::factory()->create();

        $cart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($cart)->create();

        $inactiveSecondaryCart = Cart::factory()
            ->for($user)
            ->secondary()
            ->checkedOut()
            ->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $inactiveSecondaryCart->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('destination_cart_id');
    });
});

describe('core logic and happy path', function () {
    it('moves item from main to secondary', function () {
        $user = User::factory()->create();

        $mainCart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($mainCart)->create();

        $secondaryCart = Cart::factory()->for($user)->secondary()->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $secondaryCart->id,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت انتقال یافت.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $secondaryCart->id,
        ]);
    });

    it('moves item from main to named cart', function () {
        $user = User::factory()->create();

        $mainCart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($mainCart)->create();

        $namedCart = Cart::factory()->for($user)->named()->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $namedCart->id,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت انتقال یافت.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $namedCart->id,
        ]);
    });

    it('moves item from secondary to main', function () {
        $user = User::factory()->create();

        $secondaryCart = Cart::factory()->for($user)->secondary()->create();
        $cartItem = CartItem::factory()->for($secondaryCart)->create();

        $mainCart = Cart::factory()->for($user)->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $mainCart->id,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت انتقال یافت.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $mainCart->id,
        ]);
    });

    it('moves item from secondary to named', function () {
        $user = User::factory()->create();

        $secondaryCart = Cart::factory()->for($user)->secondary()->create();
        $cartItem = CartItem::factory()->for($secondaryCart)->create();

        $namedCart = Cart::factory()->for($user)->named()->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $namedCart->id,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت انتقال یافت.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $namedCart->id,
        ]);
    });

    it('moves item from named to main', function () {
        $user = User::factory()->create();

        $namedCart = Cart::factory()->for($user)->named()->create();
        $cartItem = CartItem::factory()->for($namedCart)->create();

        $mainCart = Cart::factory()->for($user)->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $mainCart->id,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت انتقال یافت.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $mainCart->id,
        ]);
    });

    it('moves item from named to secondary', function () {
        $user = User::factory()->create();

        $namedCart = Cart::factory()->for($user)->named()->create();
        $cartItem = CartItem::factory()->for($namedCart)->create();

        $secondaryCart = Cart::factory()->for($user)->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $secondaryCart->id,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت انتقال یافت.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $secondaryCart->id,
        ]);
    });

    it('creates new item when destination cart has no equivalent product', function () {
        $user = User::factory()->create();

        $sourceCart = Cart::factory()->for($user)->create();
        $destinationCart = Cart::factory()->for($user)->secondary()->create();

        $cartItem = CartItem::factory()->for($sourceCart)->create(['quantity' => 2]);

        Sanctum::actingAs($user);

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $destinationCart->id
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت انتقال یافت.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $destinationCart->id,
            'quantity' => 2,
        ]);
    });

    it('merges quantity when destination cart already has the same product', function () {
        $user = User::factory()->create();

        $product = Product::factory()->create();

        $sourceCart = Cart::factory()->for($user)->create();
        $sourceItem = CartItem::factory()->for($sourceCart)->create([
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $destinationCart = Cart::factory()->for($user)->secondary()->create();

        $destinationItem = CartItem::factory()->for($destinationCart)->create([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        Sanctum::actingAs($user);

        postJson("/api/v1/cart-items/{$sourceItem->id}/move", [
            'destination_cart_id' => $destinationCart->id
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت انتقال یافت.');

        assertDatabaseHas('cart_items', [
            'id' => $destinationItem->id,
            'cart_id' => $destinationCart->id,
            'quantity' => 5,
        ]);

        assertDatabaseMissing('cart_items', [
            'id' => $sourceItem->id,
        ]);

        expect(
            CartItem::query()
                ->where('cart_id', $destinationCart->id)
                ->where('product_id', $product->id)
                ->count()
        )->toBe(1);
    });
});

describe('edge cases and errors', function () {
    it('denies access when user is not authenticated', function () {
        $user = User::factory()->create();

        $sourceCart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($sourceCart)->create();

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $sourceCart->id
        ])->assertUnauthorized();
    });

    it('returns 409 when moving to the same cart', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $sourceCart = Cart::factory()->for($user)->create();
        $cartItem = CartItem::factory()->for($sourceCart)->create();

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $sourceCart->id
        ])
            ->assertConflict()
            ->assertJsonPath('message', 'این مورد هم‌اکنون در همین سبد خرید قرار دارد.');
    });

    it('fails with 404 when cart item does not exist', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $sourceCart = Cart::factory()->for($user)->create();

        postJson('/api/v1/cart-items/999/move', [
            'destination_cart_id' => $sourceCart->id
        ])->assertNotFound();
    });

    it('fails when source cart is not active', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $inactiveSourceCart = Cart::factory()->for($user)->checkedOut()->create();
        $cartItem = CartItem::factory()->for($inactiveSourceCart)->create();

        $destinationCart = Cart::factory()->for($user)->secondary()->create();

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $destinationCart->id
        ])->assertForbidden();
    });

    it('fails when transferring from a locked cart (e.g payment in progress)', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $lockedSourceCart = Cart::factory()->for($user)->locked()->create();
        $cartItem = CartItem::factory()->for($lockedSourceCart)->create();

        $destinationCart = Cart::factory()->for($user)->secondary()->create();

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $destinationCart->id
        ])->assertForbidden();
    });

    it('fails when transferring to a locked cart', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $sourceCart = Cart::factory()->for($user)->secondary()->create();
        $cartItem = CartItem::factory()->for($sourceCart)->create();

        $lockedDestinationCart = Cart::factory()->for($user)->locked()->create();

        postJson("/api/v1/cart-items/{$cartItem->id}/move", [
            'destination_cart_id' => $lockedDestinationCart->id
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'در حال حاضر امکان انتقال به این سبد خرید وجود ندارد زیرا فرآیند پرداخت آن در حال انجام است.');
    });
});