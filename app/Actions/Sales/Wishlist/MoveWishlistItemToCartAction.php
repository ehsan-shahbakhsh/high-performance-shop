<?php

namespace App\Actions\Sales\Wishlist;

use App\Exceptions\BusinessException;
use App\Models\{CartItem, User, WishlistItem};
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MoveWishlistItemToCartAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, WishlistItem $wishlistItem): CartItem
    {
        return DB::transaction(function () use ($user, $wishlistItem) {
            $wishlistItem = WishlistItem::query()
                ->whereKey($wishlistItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            $mainCart = $user->mainCart()->lockForUpdate()->first();

            $variant = $wishlistItem->variant;
            if (!$variant->is_active || !$variant->product->isAvailable()) {
                throw new BusinessException(
                    'این محصول در حال حاضر غیرفعال است و امکان خرید آن وجود ندارد.',
                    httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $cartItemExists = $mainCart->items()
                ->where('product_variant_id', $wishlistItem->product_variant_id)
                ->exists();
            if ($cartItemExists) {
                throw new BusinessException(
                    'این محصول از قبل در سبد خرید شما وجود دارد.',
                    httpCode: Response::HTTP_CONFLICT,
                );
            }

            if ($variant->product->manage_stock && $variant->stock_quantity < 1) {
                throw new BusinessException(
                    'موجودی این محصول به پایان رسیده است.',
                    httpCode: Response::HTTP_CONFLICT,
                );
            }

            $cartItem = $mainCart->items()
                ->create([
                    'product_variant_id' => $wishlistItem->product_variant_id,
                    'quantity' => 1,
                    'price_when_added' => $variant->final_price,
                ]);

            $wishlistItem->deleteOrFail();

            return $cartItem;
        });
    }
}
