<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Policies\Sales\CartItemPolicy;
use App\Policies\Sales\CartPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Cart::class => CartPolicy::class,
        CartItem::class => CartItemPolicy::class,
    ];
}
