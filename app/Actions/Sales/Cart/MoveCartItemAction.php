<?php

namespace App\Actions\Sales\Cart;

use App\Enums\{CartStatus, CartType};
use App\Exceptions\{BusinessException, CartLockedException};
use App\Models\{User, CartItem};
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MoveCartItemAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, CartItem $cartItem, CartType $targetType): void
    {
        DB::transaction(function () use ($user, $cartItem, $targetType) {
            $cartItem = CartItem::query()
                ->whereKey($cartItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($cartItem->cart->type === $targetType) {
                throw new BusinessException(
                    'این مورد هم‌اکنون در همین سبد خرید قرار دارد.',
                    httpCode: Response::HTTP_CONFLICT,
                );
            }

            $destinationCart = $user->carts()
                ->where('type', $targetType)
                ->where('status', CartStatus::Active)
                ->lockForUpdate()
                ->firstOrFail();

            if ($destinationCart->type === CartType::Main && $destinationCart->isLocked()) {
                throw new CartLockedException(
                    'در حال حاضر امکان انتقال به این سبد خرید وجود ندارد زیرا فرآیند پرداخت آن در حال انجام است.',
                );
            }

            $existingItem = $destinationCart->items()
                ->where('product_variant_id', $cartItem->product_variant_id)
                ->lockForUpdate()
                ->first();

            if (!$existingItem) {
                $cartItem->update(['cart_id' => $destinationCart->id]);
                return;
            }

            $existingItem->increment('quantity', $cartItem->quantity);

            $cartItem->delete();
        });
    }
}
