<?php

namespace App\Actions\Sales\Wishlist;

use App\Exceptions\BusinessException;
use App\Models\ProductVariant;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AddWishlistItemAction
{
    /**
     * @throws Throwable
     */
    public function execute(Wishlist $wishlist, int $variantId): WishlistItem
    {
        return DB::transaction(function () use ($wishlist, $variantId) {
            $wishlist = Wishlist::query()
                ->whereKey($wishlist->id)
                ->lockForUpdate()
                ->first();

            if ($wishlist->items()->count() >= config('commerce.wishlist.max_items_per_list')) {
                throw new BusinessException(
                    'این لیست علاقه‌مندی به حداکثر تعداد آیتم مجاز رسیده است.',
                    httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $variant = ProductVariant::query()->findOrFail($variantId);

            $itemExists = WishlistItem::query()
                ->where('wishlist_id', $wishlist->id)
                ->where('product_variant_id', $variant->id)
                ->exists();

            if ($itemExists) {
                throw new BusinessException(
                    'این محصول قبلاً در این لیست وجود دارد.',
                    httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            return WishlistItem::query()->create([
                'wishlist_id' => $wishlist->id,
                'product_variant_id' => $variant->id,
            ]);
        });
    }
}
