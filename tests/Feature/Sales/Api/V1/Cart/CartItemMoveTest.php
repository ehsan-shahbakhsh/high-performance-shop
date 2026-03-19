<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{postJson, assertDatabaseHas, assertDatabaseMissing};
use App\Models\{Product, User, CartItem};
use App\Enums\CartType;
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('fails when target_type is missing', function () {
        $user = User::factory()->create();

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/carts/{$mainCart->id}/items/{$cartItem->id}/move")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('target_type');
    });

    it('fails when target_type is invalid', function () {
        $user = User::factory()->create();

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/carts/{$mainCart->id}/items/{$cartItem->id}/move", [
            'target_type' => 'foo',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('target_type');
    });
});

describe('core logic and happy path', function () {
    it('moves item from main to secondary', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $secondaryCart = $user->secondaryCart;

        $cartItem = CartItem::factory()->for($mainCart)->create();

        postJson("/api/v1/carts/{$mainCart->id}/items/{$cartItem->id}/move", [
            'target_type' => CartType::Secondary->value,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت به سبد خرید بعدی منتقل شد.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $secondaryCart->id,
        ]);
    });

    it('moves item from secondary to main', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $secondaryCart = $user->secondaryCart;
        $cartItem = CartItem::factory()->for($secondaryCart)->create();

        postJson("/api/v1/carts/{$secondaryCart->id}/items/{$cartItem->id}/move", [
            'target_type' => CartType::Main->value,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت به سبد خرید اصلی منتقل شد.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $mainCart->id,
        ]);
    });

    it('creates new item when destination cart has no equivalent product', function () {
        $user = User::factory()->create();

        $mainCart = $user->mainCart;
        $secondaryCart = $user->secondaryCart;

        $cartItem = CartItem::factory()->for($mainCart)->quantity(2)->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/carts/{$mainCart->id}/items/{$cartItem->id}/move", [
            'target_type' => CartType::Secondary->value,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت به سبد خرید بعدی منتقل شد.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'cart_id' => $secondaryCart->id,
            'quantity' => 2,
        ]);
    });

    it('merges quantity when destination cart already has the same product', function () {
        $user = User::factory()->create();

        $product = Product::factory()->create();

        $mainCart = $user->mainCart;
        $sourceItem = CartItem::factory()
            ->for($mainCart)
            ->for($product)
            ->quantity(3)
            ->create();

        $secondaryCart = $user->secondaryCart;
        $destinationItem = CartItem::factory()
            ->for($secondaryCart)
            ->for($product)
            ->quantity(2)
            ->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/carts/{$mainCart->id}/items/{$sourceItem->id}/move", [
            'target_type' => CartType::Secondary->value,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت به سبد خرید بعدی منتقل شد.');

        assertDatabaseHas('cart_items', [
            'id' => $destinationItem->id,
            'cart_id' => $secondaryCart->id,
            'quantity' => 5,
        ]);

        assertDatabaseMissing('cart_items', [
            'id' => $sourceItem->id,
        ]);

        expect(
            CartItem::query()
                ->where('cart_id', $secondaryCart->id)
                ->where('product_id', $product->id)
                ->count()
        )->toBe(1);
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized when user is not authenticated', function () {
        $user = User::factory()->create();

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->create();

        postJson("/api/v1/carts/{$mainCart->id}/items/{$cartItem->id}/move", [
            'target_type' => CartType::Secondary->value,
        ])->assertUnauthorized();
    });

    it('fails when moving item to the same cart type', function () {
        $user = User::factory()->create();

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->create();

        Sanctum::actingAs($user);

        postJson("/api/v1/carts/{$mainCart->id}/items/{$cartItem->id}/move", [
            'target_type' => CartType::Main->value,
        ])
            ->assertConflict();
    });

    it('returns 409 when moving to the same cart', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->create();

        postJson("/api/v1/carts/{$mainCart->id}/items/{$cartItem->id}/move", [
            'target_type' => CartType::Main->value,
        ])
            ->assertConflict()
            ->assertJsonPath('message', 'این مورد هم‌اکنون در همین سبد خرید قرار دارد.');
    });

    it('fails with 404 when cart item does not exist', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;

        postJson("/api/v1/carts/{$mainCart->id}/items/99999/move", [
            'target_type' => CartType::Main->value,
        ])->assertNotFound();
    });

    it('fails with 404 when cart item does not belong to the cart', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $secondaryCart = $user->secondaryCart;

        $cartItem = CartItem::factory()->for($secondaryCart)->create();

        postJson("/api/v1/carts/{$mainCart->id}/items/{$cartItem->id}/move", [
            'target_type' => CartType::Secondary->value,
        ])->assertNotFound();
    });

    it('fails when transferring from main locked cart (e.g payment in progress)', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $mainCart->lock(fake()->uuid());
        $cartItem = CartItem::factory()->for($mainCart)->create();

        postJson("/api/v1/carts/{$mainCart->id}/items/{$cartItem->id}/move", [
            'target_type' => CartType::Secondary->value,
        ])->assertForbidden();
    });

    it('fails when transferring to main locked cart', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $secondaryCart = $user->secondaryCart;

        $mainCart->lock(fake()->uuid());

        $cartItem = CartItem::factory()->for($secondaryCart)->create();

        postJson("/api/v1/carts/{$secondaryCart->id}/items/{$cartItem->id}/move", [
            'target_type' => CartType::Main->value,
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'در حال حاضر امکان انتقال به این سبد خرید وجود ندارد زیرا فرآیند پرداخت آن در حال انجام است.');
    });
});