<?php

namespace App\Actions\Sales\Cart;

use App\Exceptions\BusinessException;
use App\Models\{Cart, CartItem};
use App\Services\Sales\Cart\CartCalculator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class UpdateCartItemAction
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
    public function execute(CartItem $cartItem, int $quantity): CartItem
    {
        return DB::transaction(function () use ($cartItem, $quantity) {
            $cart = Cart::query()
                ->whereKey($cartItem->cart_id)
                ->lockForUpdate()
                ->firstOrFail();

            $cartItem = CartItem::query()
                ->with(['product', 'variant'])
                ->whereKey($cartItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($quantity <= 0) {
                $cartItem->delete();

                $this->cartCalculator->recalculate($cart);

                return $cartItem;
            }

            $product = $cartItem->product;
            $variant = $cartItem->variant;

            if ($product->manage_stock && $variant && $variant->stock_quantity < $quantity) {
                throw new BusinessException(
                    'موجودی کافی نیست یا کالا در سبد دیگران رزرو شده است.',
                    httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $cartItem->update(['quantity' => $quantity]);

            $this->cartCalculator->recalculate($cart);

            return $cartItem;
        });
    }
}
