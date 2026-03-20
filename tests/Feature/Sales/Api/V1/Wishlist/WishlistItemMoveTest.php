<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{postJson, assertDatabaseHas, assertDatabaseMissing};
use App\Models\{User, Wishlist, Product, ProductVariant, WishlistItem, CartItem};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('validation', function () {
    it('validates variant_id must be integer', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create();

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart", [
            'variant_id' => 'invalid'
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variant_id']);
    });

    it('fails when the given variant_id does not exist', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create();

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart", [
            'variant_id' => '99999'
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variant_id']);
    });

    it('fails when the selected variant is inactive', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->for($product)->create(['is_active' => false]);

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart", [
            'variant_id' => $variant->id
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variant_id']);
    });

    it('fails when the variant does not belong to the wishlist item product', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->create();

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart", [
            'variant_id' => $variant->id
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variant_id']);
    });
});

describe('core logic and happy path', function () {
    it('moves wishlist item to cart without variant', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = $user->mainCart;

        $product = Product::factory()->simple()->create();

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت به سبد خرید افزوده شد.');

        assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => 1,
        ]);

        assertDatabaseMissing('wishlist_items', [
            'id' => $wishlistItem->id
        ]);
    });

    it('moves wishlist item to cart with variant', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = $user->mainCart;

        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->for($variant, 'variant')
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت به سبد خرید افزوده شد.');

        assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
        ]);
    });

    it('auto selects variant when product has only one variant', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت به سبد خرید افزوده شد.');

        assertDatabaseHas('cart_items', [
            'variant_id' => $variant->id
        ]);
    });

    it('moves item using selected variant', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart", ['variant_id' => $variant->id])
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت به سبد خرید افزوده شد.');

        assertDatabaseHas('cart_items', [
            'variant_id' => $variant->id
        ]);
    });

    it('stores correct price snapshot in cart item', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = $user->mainCart;

        $product = Product::factory()->create(['price' => 150000]);

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت به سبد خرید افزوده شد.');

        assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'unit_price_snapshot' => 150000
        ]);
    });
});

describe('edge cases and errors', function () {
    it('returns unauthorized when user is not authenticated', function () {
        $wishlist = Wishlist::factory()->create();
        $item = WishlistItem::factory()->for($wishlist)->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$item->id}/move-to-cart")
            ->assertUnauthorized();
    });

    it('returns conflict when item already exists in cart', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = $user->mainCart;

        $product = Product::factory()->simple()->create();

        CartItem::factory()->for($cart)->for($product)->create();

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertConflict()
            ->assertJsonPath('message', 'این محصول از قبل در سبد خرید شما وجود دارد.');
    });

    it('returns error when variant selection is required', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->variable()->create();

        ProductVariant::factory()->count(2)->for($product)->create();

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VARIANT_SELECTION_REQUIRED');
    });

    it('returns error when product is inactive', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['is_active' => false]);

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertUnprocessable();
    });

    it('cannot move wishlist item that belongs to another user', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $otherUser = User::factory()->create();

        $product = Product::factory()->create();

        $wishlist = $otherUser->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertForbidden();
    });

    it('returns error when variant does not belong to the product', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create();
        $anotherProduct = Product::factory()->variable()->create();

        $variant = ProductVariant::factory()->for($anotherProduct)->create();

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart", ['variant_id' => $variant->id])
            ->assertUnprocessable();
    });

    it('returns out of stock when variant stock is zero', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['manage_stock' => true]);
        $variant = ProductVariant::factory()->for($product)->create(['stock_quantity' => 0]);

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($product)
            ->for($variant, 'variant')
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertConflict();
    });
});
