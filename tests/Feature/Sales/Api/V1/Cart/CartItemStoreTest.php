<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{postJson, assertDatabaseHas};
use Laravel\Sanctum\Sanctum;
use App\Models\{Product, Cart, User, ProductVariant, CartItem};
use App\Enums\ProductType;
use Carbon\Carbon;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('fails when product does not exist', function () {
        postJson('/api/v1/cart-items', [
            'product_id' => 999,
            'quantity' => 1,
        ], [
            'Session-Id' => fake()->uuid(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');
    });

    it('fails when variant does not belong to product', function () {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create();

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ], [
            'Session-Id' => fake()->uuid(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
    });

    it('fails if neither user nor session id provided', function () {
        $product = Product::factory()->simple()->create();

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id',
                'session_id',
            ]);
    });

    it('fails when quantity is less than one', function () {
        $product = Product::factory()->simple()->create();

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'quantity' => 0,
        ], [
            'Session-Id' => fake()->uuid(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    });

    it('fails when variant is required but not provided', function () {
        $variableProduct = Product::factory()
            ->has(ProductVariant::factory()->count(3), 'variants')
            ->create(['type' => ProductType::Variable]);
        $virtualProduct = Product::factory()
            ->has(ProductVariant::factory()->count(3), 'variants')
            ->create(['type' => ProductType::Virtual]);

        postJson('/api/v1/cart-items', [
            'product_id' => $variableProduct->id,
            'quantity' => 1,
        ], [
            'Session-Id' => fake()->uuid(),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.variant_id.0', 'برای این محصول باید یک تنوع (رنگ/سایز) انتخاب کنید.');


        postJson('/api/v1/cart-items', [
            'product_id' => $virtualProduct->id,
            'quantity' => 1,
        ], [
            'Session-Id' => fake()->uuid(),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.variant_id.0', 'برای این محصول باید یک تنوع انتخاب کنید.');
    });
});

describe('core logic and happy path', function () {
    it('creates cart if none exists for session', function () {
        $sessionId = fake()->uuid();
        $product = Product::factory()->simple()->create();

        expect(Cart::query()->where('session_id', $sessionId)->count())->toBe(0);

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ], [
            'Session-Id' => $sessionId,
        ])->assertOk();

        expect(Cart::query()->where('session_id', $sessionId)->count())->toBe(1);
    });

    it('creates new cart item when product not in cart', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $product = Product::factory()->simple()->create();

        expect(CartItem::query()->where('cart_id', $mainCart->id)->count())->toBe(0);

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertOk();

        $item = CartItem::query()->where('cart_id', $mainCart->id)->first();

        expect($item)->not->toBeNull()
            ->and($item)
            ->product_id->toBe($product->id)
            ->quantity->toBe(1);
    });

    it('increments quantity when item already exists', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $product = Product::factory()->simple()->create();

        $cartItem = CartItem::factory()->quantity(10)->for($product)->for($mainCart)->create();

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ])->assertOk();

        expect($cartItem->fresh()->quantity)->toBe(15);
    });

    it('stores price snapshot when item is created', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $product = Product::factory()->simple()->create([
            'price' => 10_000,
            'sale_price' => 7_000,
        ]);
        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ])->assertOk();

        $item = CartItem::query()->where('cart_id', $mainCart->id)->first();

        expect($item)->not->toBeNull()
            ->and($item)
            ->product_id->toBe($product->id)
            ->quantity->toBe(5)
            ->unit_price_snapshot->toBe(7_000);
    });

    it('updates cart version after modification', function () {
        $user = User::factory()->create();

        $mainCart = $user->mainCart;
        $product = Product::factory()->simple()->create();

        $initialVersion = $mainCart->version;

        Sanctum::actingAs($user);

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertOk();

        expect(Cart::query()->where('user_id', $user->id)->count())->toBe(2)
            ->and($mainCart->fresh()->version)->toBe($initialVersion + 1);

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertOk();

        expect(Cart::query()->where('user_id', $user->id)->count())->toBe(2)
            ->and($mainCart->fresh()->version)->toBe($initialVersion + 2);
    });

    it('updates cart last_activity_at timestamp', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        Carbon::setTestNow(now());

        $mainCart = $user->mainCart;
        $mainCart->update(['last_activity_at' => now()]);

        $product = Product::factory()->simple()->create();

        Carbon::setTestNow(now()->addMinutes(5));

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertOk();

        expect($mainCart->fresh()->last_activity_at->timestamp)
            ->toBe(now()->timestamp);
    });

    it('recalculates cart totals after adding item', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $mainCart->update(['subtotal' => 0]);

        $productA = Product::factory()->simple()->create(['sale_price' => 100]);
        $productB = Product::factory()->simple()->create(['sale_price' => 300]);

        postJson('/api/v1/cart-items', [
            'product_id' => $productA->id,
            'quantity' => 2,
        ])->assertOk();

        $mainCart->refresh();

        expect($mainCart->subtotal)->toBe(200)
            ->and($mainCart->items_count)->toBe(1)
            ->and($mainCart->items_qty_sum)->toBe(2);

        postJson('/api/v1/cart-items', [
            'product_id' => $productB->id,
            'quantity' => 5,
        ])->assertOk();

        $mainCart->refresh();

        expect($mainCart->subtotal)->toBe(1700)
            ->and($mainCart->items_count)->toBe(2)
            ->and($mainCart->items_qty_sum)->toBe(7);
    });

    it('creates separate cart items when different variants of the same product are added', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;

        $product = Product::factory()->variable()->create(['manage_stock' => false]);
        $variantA = ProductVariant::factory()->create(['product_id' => $product->id, 'sale_price' => 100]);
        $variantB = ProductVariant::factory()->create(['product_id' => $product->id, 'sale_price' => 150]);

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'variant_id' => $variantA->id,
            'quantity' => 2,
        ])->assertOk();

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'variant_id' => $variantB->id,
            'quantity' => 3,
        ])->assertOk();

        expect(CartItem::query()->where('cart_id', $mainCart->id)->count())->toBe(2);

        $itemA = CartItem::query()->where('cart_id', $mainCart->id)->where('variant_id', $variantA->id)->first();
        expect($itemA->quantity)->toBe(2)
            ->and($itemA->unit_price_snapshot)->toEqual(100);

        $itemB = CartItem::query()->where('cart_id', $mainCart->id)->where('variant_id', $variantB->id)->first();
        expect($itemB->quantity)->toBe(3)
            ->and($itemB->unit_price_snapshot)->toEqual(150);
    });

    it('adds item to existing cart', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $product = Product::factory()->simple()->create();

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertOk();

        assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'cart_id' => $mainCart->id,
        ]);
    });

    it('increments quantity instead of creating duplicate cart items on rapid requests', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $product = Product::factory()->simple()->create();

        for ($i = 0; $i < 10; $i++) {
            postJson('/api/v1/cart-items', [
                'product_id' => $product->id,
                'quantity' => 1,
            ])->assertOk();
        }

        expect(
            CartItem::query()
                ->where('cart_id', $mainCart->id)
                ->where('product_id', $product->id)
                ->count()
        )->toBe(1);

        assertDatabaseHas('cart_items', [
            'cart_id' => $mainCart->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);
    });
});

describe('edge cases and errors', function () {
    it('fails when stock is insufficient', function () {
        $product = Product::factory()->create(['manage_stock' => true]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 1,
        ]);

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 5,
        ], [
            'Session-Id' => fake()->uuid(),
        ])->assertUnprocessable();
    });

    it('fails when product is inactive', function () {
        $product = Product::factory()->create(['is_active' => false]);

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ], [
            'Session-Id' => fake()->uuid(),
        ])->assertUnprocessable();
    });

    it('fails when variant is inactive', function () {
        $product = Product::factory()->create();

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_active' => false,
        ]);

        postJson('/api/v1/cart-items', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ], [
            'Session-Id' => fake()->uuid(),
        ])->assertUnprocessable();
    });
});
