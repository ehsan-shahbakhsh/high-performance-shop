<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{postJson, assertDatabaseHas};
use Laravel\Sanctum\Sanctum;
use App\Models\{Product, Cart, User, ProductVariant, CartItem};
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('fails when variant does not exist', function () {
        postJson('/api/v1/cart-items', [
            'variant_id' => 999,
            'quantity' => 1,
        ], [
            'Session-Id' => fake()->uuid(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
    });

    it('fails if neither user nor session id provided', function () {
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create();

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id',
                'session_id',
            ]);
    });

    it('fails when quantity is less than one', function () {
        $variant = ProductVariant::factory()->create();

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => 0,
        ], [
            'Session-Id' => fake()->uuid(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    });

    it('fails when variant is inactive', function () {
        $inactiveVariant = ProductVariant::factory()->create(['is_active' => false]);

        postJson('/api/v1/cart-items', [
            'variant_id' => $inactiveVariant->id,
            'quantity' => 1,
        ], ['Session-Id' => fake()->uuid()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
    });

    it('fails when the parent product is inactive', function () {
        $inactiveProduct = Product::factory()->create(['is_active' => false]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $inactiveProduct->id,
            'is_active' => true,
        ]);

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ], ['Session-Id' => fake()->uuid()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
    });

    it('fails when quantity is not an integer', function () {
        $variant = ProductVariant::factory()->create();

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => 'two',
        ], ['Session-Id' => fake()->uuid()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    });
});

describe('core logic and happy path', function () {
    it('successfully adds a new variant to the cart for an authenticated user', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create(['is_active' => true, 'stock_quantity' => 1]);
        $quantity = 1;

        expect($mainCart->items()->count())->toBe(0);

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => $quantity,
        ])->assertOk();

        assertDatabaseHas('cart_items', [
            'cart_id' => $mainCart->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    });

    it('successfully adds a variant to the cart for a guest user', function () {
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create(['is_active' => true, 'stock_quantity' => 2]);
        $sessionId = fake()->uuid();

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ], ['Session-Id' => $sessionId])
            ->assertOk();

        $cartData = Redis::get("cart:$sessionId");
        expect($cartData)->not->toBeNull()
            ->and(json_decode($cartData, true))
            ->toHaveLength(1);
    });

    it('increments quantity when item already exists with user', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create(['stock_quantity' => 15]);

        $cartItem = CartItem::factory()
            ->quantity(10)
            ->for($variant, 'variant')
            ->for($mainCart)
            ->create();

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => 5,
        ])->assertOk();

        expect($cartItem->fresh()->quantity)->toBe(15);
    });

    it('stores price when item is created', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create([
                'price' => 10_000,
                'sale_price' => 7_000,
                'stock_quantity' => 5,
            ]);
        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => 5,
        ])->assertOk();

        $item = CartItem::query()->where('cart_id', $mainCart->id)->first();

        expect($item)->not->toBeNull()
            ->and($item)
            ->product_variant_id->toBe($variant->id)
            ->quantity->toBe(5)
            ->price_when_added->toBe(7_000);
    });

    it('updates cart version after modification', function () {
        $user = User::factory()->create();

        $mainCart = $user->mainCart;
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create(['stock_quantity' => 4]);

        $initialVersion = $mainCart->version;

        Sanctum::actingAs($user);

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertOk();

        expect(Cart::query()->where('user_id', $user->id)->count())->toBe(2)
            ->and($mainCart->fresh()->version)->toBe($initialVersion + 1);

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
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

        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create(['stock_quantity' => 2]);

        Carbon::setTestNow(now()->addMinutes(5));

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
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

        $variantA = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create([
                'price' => 150,
                'sale_price' => 100,
                'stock_quantity' => 2,
            ]);
        $variantB = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create([
                'price' => 400,
                'sale_price' => 300,
                'stock_quantity' => 5,
            ]);

        postJson('/api/v1/cart-items', [
            'variant_id' => $variantA->id,
            'quantity' => 2,
        ])->assertOk();

        $mainCart->refresh();

        expect($mainCart->subtotal)->toBe(300)
            ->and($mainCart->total)->toBe(200)
            ->and($mainCart->discount_total)->toBe(100)
            ->and($mainCart->items_count)->toBe(1)
            ->and($mainCart->items_qty_sum)->toBe(2);

        postJson('/api/v1/cart-items', [
            'variant_id' => $variantB->id,
            'quantity' => 5,
        ])->assertOk();

        $mainCart->refresh();

        expect($mainCart->subtotal)->toBe(2300)
            ->and($mainCart->total)->toBe(1700)
            ->and($mainCart->discount_total)->toBe(600)
            ->and($mainCart->items_count)->toBe(2)
            ->and($mainCart->items_qty_sum)->toBe(7);
    });

    it('increments quantity instead of creating duplicate cart items on rapid requests', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $mainCart = $user->mainCart;
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create(['stock_quantity' => 10]);

        for ($i = 0; $i < 10; $i++) {
            postJson('/api/v1/cart-items', [
                'variant_id' => $variant->id,
                'quantity' => 1,
            ])->assertOk();
        }

        expect(
            CartItem::query()
                ->where('cart_id', $mainCart->id)
                ->where('product_variant_id', $variant->id)
                ->count()
        )->toBe(1);

        assertDatabaseHas('cart_items', [
            'cart_id' => $mainCart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 10,
        ]);
    });

    it('uses original price when sale_price is null', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create([
                'price' => 15_000,
                'sale_price' => null,
            ]);

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
            'price_when_added' => 15_000,
        ]);
    });
});

describe('edge cases and errors', function () {
    it('fails when adding a product that is out of stock', function () {
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published()->state(['manage_stock' => true]))
            ->create(['stock_quantity' => 1]);

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => 5,
        ], [
            'Session-Id' => fake()->uuid(),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'موجودی کافی نیست یا کالا در سبد دیگران رزرو شده است.');
    });

    it('fails when total quantity (existing + new) exceeds available stock', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $variant = ProductVariant::factory()
            ->for(Product::factory()->published()->state(['manage_stock' => true]))
            ->create(['stock_quantity' => 4]);

        $mainCart = $user->mainCart;

        CartItem::factory()
            ->for($variant, 'variant')
            ->for($mainCart)
            ->create(['quantity' => 2]);

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => 3,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'موجودی کافی نیست یا کالا در سبد دیگران رزرو شده است.');

        assertDatabaseHas('cart_items', [
            'cart_id' => $mainCart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    });

    it('fails for guest user without a valid session id', function () {
        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create(['stock_quantity' => 10]);

        postJson('/api/v1/cart-items', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ], [
            'Session-Id' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('session_id');
    });
});
