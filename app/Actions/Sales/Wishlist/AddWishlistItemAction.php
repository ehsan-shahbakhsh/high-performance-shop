<?php

namespace App\Actions\Sales\Wishlist;

use App\Exceptions\BusinessException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AddWishlistItemAction
{
    /**
     * @throws Throwable
     */
    public function execute(Wishlist $wishlist, int $productId, ?int $variantId): WishlistItem
    {
        return DB::transaction(function () use ($wishlist, $productId, $variantId) {
            Wishlist::query()
                ->whereKey($wishlist->id)
                ->lockForUpdate()
                ->first();

            $product = Product::query()->findOrFail($productId);

            $variant = null;

            if ($variantId) {
                $variant = ProductVariant::query()->findOrFail($variantId);

                if ($variant->product_id !== $product->id) {
                    throw new BusinessException(
                        'تنوع انتخاب شده متعلق به این محصول نمی‌باشد.',
                        code: Response::HTTP_UNPROCESSABLE_ENTITY,
                    );
                }
            }

            $itemExists = WishlistItem::query()
                ->where('wishlist_id', $wishlist->id)
                ->where('product_id', $product->id)
                ->when($variant, fn(Builder $query) => $query->where('variant_id', $variant->id))
                ->exists();

            if ($itemExists) {
                throw new BusinessException(
                    'این محصول قبلاً در این لیست وجود دارد.',
                    httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            return WishlistItem::query()->create([
                'wishlist_id' => $wishlist->id,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
            ]);
        });
    }
}
