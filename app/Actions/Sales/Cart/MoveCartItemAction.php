<?php

namespace App\Actions\Sales\Cart;

use App\Enums\CartStatus;
use App\Enums\CartType;
use App\Exceptions\BusinessException;
use App\Exceptions\CartLockedException;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MoveCartItemAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, CartItem $cartItem, ?string $destinationCartId): void
    {
        DB::transaction(function () use ($user, $cartItem, $destinationCartId) {
            $cartItem = CartItem::query()
                ->whereKey($cartItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($destinationCartId) {
                $destinationCart = $user->carts()
                    ->whereKey($destinationCartId)
                    ->lockForUpdate()
                    ->firstOrFail();
            } else {
                $destinationCart = $user->carts()
                    ->where('type', CartType::Secondary)
                    ->where('status', CartStatus::Active)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            if ($destinationCart->type === CartType::Main && $destinationCart->isLocked()) {
                throw new CartLockedException(
                    'در حال حاضر امکان انتقال به این سبد خرید وجود ندارد زیرا فرآیند پرداخت آن در حال انجام است.',
                );
            }

            if ($cartItem->cart_id == $destinationCartId) {
                throw new BusinessException(
                    'این مورد هم‌اکنون در همین سبد خرید قرار دارد.',
                    httpCode: Response::HTTP_CONFLICT,
                );
            }

            $existingItem = $destinationCart->items()
                ->where('product_id', $cartItem->product_id)
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
