<?php

namespace App\Providers;

use App\Models\{Cart, CartItem, Wishlist, WishlistItem, Address};
use App\Policies\Sales\{CartPolicy, CartItemPolicy, WishlistPolicy, WishlistItemPolicy};
use App\Policies\Customer\AddressPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Cart::class => CartPolicy::class,
        CartItem::class => CartItemPolicy::class,
        Wishlist::class => WishlistPolicy::class,
        WishlistItem::class => WishlistItemPolicy::class,
        Address::class => AddressPolicy::class,
    ];
}
