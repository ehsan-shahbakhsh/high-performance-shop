<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Policies\Sales\CartItemPolicy;
use App\Policies\Sales\CartPolicy;
use App\Policies\Sales\WishlistItemPolicy;
use App\Policies\Sales\WishlistPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Cart::class => CartPolicy::class,
        CartItem::class => CartItemPolicy::class,
        Wishlist::class => WishlistPolicy::class,
        WishlistItem::class => WishlistItemPolicy::class,
    ];
}
