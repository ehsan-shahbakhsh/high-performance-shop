<?php

namespace App\Actions\Sales\Cart;

use App\Exceptions\BusinessException;
use App\Models\Cart;
use App\Services\Sales\Cart\CartCalculator;
use Illuminate\Support\Facades\DB;
use Throwable;

readonly class RemoveCouponAction
{
    /**
     * Create a new class instance.
     */
    public function __construct(private CartCalculator $cartCalculator) {}

    /**
     * @throws BusinessException
     * @throws Throwable
     */
    public function execute(Cart $cart): void
    {
        if (! $cart->coupon_id) {
            throw new BusinessException('هیچ کد تخفیفی روی این سبد خرید اعمال نشده است.');
        }

        DB::transaction(function () use ($cart) {
            $cart = Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();

            $cart->update(['coupon_id' => null]);

            $this->cartCalculator->recalculate($cart);
        });
    }
}
