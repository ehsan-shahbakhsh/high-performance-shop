<?php

namespace App\Actions\Sales\Cart;

use App\Models\{Cart, CartItem, User};
use App\Services\Sales\Cart\CartCalculator;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class RemoveItemFromCartAction
{
    /**
     * Create a new class instance.
     */
    public function __construct(private CartCalculator $cartCalculator)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(?User $user, CartItem $cartItem): void
    {
        DB::transaction(function () use ($cartItem, $user) {
            $cart = Cart::query()
                ->whereKey($cartItem->cart_id)
                ->lockForUpdate()
                ->firstOrFail();

            $cartItem = CartItem::query()
                ->whereKey($cartItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            $cartItem->delete();

            $this->cartCalculator->recalculate($cart);
        });
    }
}
