<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use function Pest\Laravel\{postJson, assertDatabaseHas, assertDatabaseMissing};
use App\Models\{User, Wishlist, Product, ProductVariant, WishlistItem, CartItem};
use Laravel\Sanctum\Sanctum;

uses(TestCase::class, RefreshDatabase::class);

describe('core logic and happy path', function () {
    it('moves wishlist item to cart', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = $user->mainCart;

        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create();

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($variant, 'variant')
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت به سبد خرید افزوده شد.');

        assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        assertDatabaseMissing('wishlist_items', [
            'id' => $wishlistItem->id
        ]);
    });

    it('stores correct price in cart item', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $cart = $user->mainCart;

        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create(['sale_price' => null, 'price' => 150000]);

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($variant, 'variant')
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertOk()
            ->assertJsonPath('message', 'محصول با موفقیت به سبد خرید افزوده شد.');

        assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'price_when_added' => 150000,
        ]);
    });

    it('allows adding out of stock variant to wishlist', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create(['stock_quantity' => 0]);

        postJson("/api/v1/wishlists/{$wishlist->id}/items", ['variant_id' => $variant->id])
            ->assertCreated()
            ->assertJsonPath('message', 'محصول به لیست اضافه شد.');

        assertDatabaseHas('wishlist_items', [
            'wishlist_id' => $wishlist->id,
            'product_variant_id' => $variant->id
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

        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create();

        CartItem::factory()->for($cart)->for($variant, 'variant')->create();

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($variant, 'variant')
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertConflict()
            ->assertJsonPath('message', 'این محصول از قبل در سبد خرید شما وجود دارد.');
    });

    it('fails to move if the variant is inactive', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $variant = ProductVariant::factory()->create(['is_active' => false]);

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($variant, 'variant')
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'این محصول در حال حاضر غیرفعال است و امکان خرید آن وجود ندارد.');
    });

    it('fails to move if the parent product is inactive', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $variant = ProductVariant::factory()
            ->for(Product::factory()->state(['is_active' => false]))
            ->create(['is_active' => true]);

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($variant, 'variant')
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'این محصول در حال حاضر غیرفعال است و امکان خرید آن وجود ندارد.');

        assertDatabaseHas('wishlist_items', [
            'id' => $wishlistItem->id
        ]);
    });

    it('cannot move wishlist item that belongs to another user', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $otherUser = User::factory()->create();

        $variant = ProductVariant::factory()->create();

        $wishlist = $otherUser->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($variant, 'variant')
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertForbidden();
    });

    it('fails to move if the variant is out of stock', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $variant = ProductVariant::factory()
            ->for(Product::factory()->published()->state(['manage_stock' => true]))
            ->create(['stock_quantity' => 0]);

        $wishlist = $user->defaultWishlist;
        $wishlistItem = WishlistItem::factory()
            ->for($wishlist)
            ->for($variant, 'variant')
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items/{$wishlistItem->id}/move-to-cart")
            ->assertConflict()
            ->assertJsonPath('message', 'موجودی این محصول به پایان رسیده است.');

        assertDatabaseHas('wishlist_items', [
            'id' => $wishlistItem->id
        ]);

        assertDatabaseMissing('cart_items', [
            'product_variant_id' => $variant->id,
        ]);
    });

    it('prevents adding item if wishlist exceeds maximum capacity', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $wishlist = Wishlist::factory()->for($user)->create();

        WishlistItem::factory()->count(100)->for($wishlist)->create();

        $variant = ProductVariant::factory()
            ->for(Product::factory()->published())
            ->create();

        postJson("/api/v1/wishlists/{$wishlist->id}/items", ['variant_id' => $variant->id])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'این لیست علاقه‌مندی به حداکثر تعداد آیتم مجاز رسیده است.');
    });
});
