<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{patchJson, assertDatabaseHas, assertDatabaseMissing};
use App\Models\{User, CartItem, Product, ProductVariant};
use App\Enums\CartStatus;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('validates quantity field correctly', function ($invalidQuantity) {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", [
            'quantity' => $invalidQuantity
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    })->with([
        'missing' => null,
        'string' => 'foo',
        'negative' => -1,
    ]);
});

describe('core logic and happy path', function () {
    it('removes cart item when quantity is zero', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()->for($mainCart)->quantity(2)->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 0])
            ->assertOk()
            ->assertJsonPath('message', 'محصول از سبد خرید حذف شد.');

        assertDatabaseMissing('cart_items', [
            'id' => $cartItem->id,
        ]);
    });

    it('guest can update own cart item', function () {
        $sessionId = fake()->uuid();

        $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
        Redis::setex("cart:$sessionId", 14 * 86400, json_encode([
            $variant->id => [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
                'price_when_added' => $variant->final_price,
            ],
        ]));

        patchJson("/api/v1/cart-items/{$variant->id}", ['quantity' => 3], ['Session-Id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('message', 'تعداد محصول در سبد خرید بروزرسانی شد.');

        $items = json_decode(Redis::get("cart:$sessionId"), true);
        expect($items[$variant->id]['quantity'])->toBe(3);
    });

    it('updates quantity correctly for authenticated user', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cartItem = CartItem::factory()
            ->for(ProductVariant::factory()->state(['stock_quantity' => 5]), 'variant')
            ->for($user->mainCart)
            ->quantity(2)
            ->create();

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 5])
            ->assertOk()
            ->assertJsonPath('message', 'تعداد محصول در سبد خرید بروزرسانی شد.');

        assertDatabaseHas('cart_items', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);

        patchJson("/api/v1/cart-items/{$cartItem->id}", ['quantity' => 3])
            ->assertOk();

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

        $item1 = CartItem::factory()
            ->for($mainCart)
            ->for(ProductVariant::factory()->state(['stock_quantity' => 5]), 'variant')
            ->quantity(2)
            ->price(500)
            ->create();

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

    it('updates cart version and last_activity_at when an item is updated', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $cartItem = CartItem::factory()
            ->for(ProductVariant::factory()->state(['stock_quantity' => 5]), 'variant')
            ->for($mainCart)
            ->quantity(2)
            ->create();

        $initialVersion = $mainCart->version;

        $initialActivity = now()->subMinutes(10);
        $mainCart->update(['last_activity_at' => $initialActivity]);

        $now = now();
        Carbon::setTestNow($now);

        patchJson("/api/v1/cart-items/{$cartItem->id}", [
            'quantity' => 5
        ])->assertOk();

        $mainCart->refresh();

        expect($mainCart->version)->toBe($initialVersion + 1)
            ->and($mainCart->last_activity_at->timestamp)->toBe($now->timestamp);
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

        $variant = ProductVariant::factory()
            ->for(Product::factory()->state(['manage_stock' => true]))
            ->create(['stock_quantity' => 5]);
        $cartItem = CartItem::factory()
            ->for($mainCart)
            ->for($variant, 'variant')
            ->create();

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
