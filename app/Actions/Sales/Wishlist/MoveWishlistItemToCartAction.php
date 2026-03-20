<?php

namespace App\Actions\Sales\Wishlist;

use App\Enums\ProductType;
use App\Exceptions\BusinessException;
use App\Services\Catalog\ProductPriceResolver;
use App\Models\{CartItem, ProductVariant, User, WishlistItem};
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class MoveWishlistItemToCartAction
{
    /**
     * Create a new class instance.
     */
    public function __construct(private ProductPriceResolver $priceResolver)
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(User $user, WishlistItem $wishlistItem, ?int $variantId): CartItem
    {
        return DB::transaction(function () use ($user, $wishlistItem, $variantId) {
            $wishlistItem = WishlistItem::query()
                ->whereKey($wishlistItem->id)
                ->lockForUpdate()
                ->firstOrFail();

            $mainCart = $user->mainCart()->lockForUpdate()->first();

            $product = $wishlistItem->product;
            if (!$product) {
                throw new BusinessException(
                    'محصول مورد نظر یافت نشد.',
                    httpCode: Response::HTTP_NOT_FOUND,
                );
            }
            if (!$product->is_active) {
                throw new BusinessException(
                    'این محصول در حال حاضر غیرفعال است و امکان خرید آن وجود ندارد.',
                    httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $variant = $variantId
                ? ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->whereKey($variantId)
                    ->first()
                : $wishlistItem->variant;

            if (!$variant) {
                $variantsCount = $product->variants()->count();

                if ($variantsCount === 1) {
                    $variant = $product->variants()->first();
                } elseif ($variantsCount > 1) {
                    throw new BusinessException(
                        'لطفاً ابتدا تنوع مورد نظر این محصول را انتخاب کنید.',
                        httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                        errorCode: 'VARIANT_SELECTION_REQUIRED',
                    );
                }
            }

            if ($variant && !$variant->is_active) {
                throw new BusinessException(
                    'این تنوع از محصول در حال حاضر در دسترس نیست.',
                    httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            if ($variant && $product->manage_stock && $variant->stock_quantity < 1) {
                throw new BusinessException(
                    'موجودی این محصول به پایان رسیده است.',
                    httpCode: Response::HTTP_CONFLICT,
                );
            }

            $cartItemExists = $mainCart->items()
                ->where('product_id', $wishlistItem->product_id)
                ->when(
                    $variant,
                    fn($query) => $query->where('variant_id', $variant->id),
                    fn($query) => $query->whereNull('variant_id')
                )
                ->exists();
            if ($cartItemExists) {
                throw new BusinessException(
                    'این محصول از قبل در سبد خرید شما وجود دارد.',
                    httpCode: Response::HTTP_CONFLICT,
                );
            }

            $cartItem = $mainCart->items()
                ->create([
                    'product_id' => $wishlistItem->product_id,
                    'variant_id' => $variant?->id,
                    'quantity' => 1,
                    'unit_price_snapshot' => $this->priceResolver->resolve($product, $variant),
                ]);

            $wishlistItem->deleteOrFail();

            return $cartItem;
        });
    }
}
