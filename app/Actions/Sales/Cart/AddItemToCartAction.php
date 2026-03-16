<?php

namespace App\Actions\Sales\Cart;

use App\Data\Sales\AddItemToCartData;
use App\Enums\{CartType, CartStatus};
use App\Exceptions\BusinessException;
use App\Models\{Cart, CartItem, Product, ProductVariant};
use App\Services\Catalog\ProductPriceResolver;
use App\Services\Sales\Cart\CartCalculator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class AddItemToCartAction
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private ProductPriceResolver $priceResolver,
        private CartCalculator       $cartCalculator,
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(AddItemToCartData $data): CartItem
    {
        return DB::transaction(function () use ($data) {
            $product = Product::query()->findOrFail($data->productId);
            $variant = ProductVariant::query()
                ->where('product_id', $data->productId)
                ->whereKey($data->variantId)
                ->lockForUpdate()
                ->first();

            $cart = Cart::query()
                ->whereKey($this->getOrCreateCart($data)->id)
                ->lockForUpdate()
                ->first();

            $cartItem = $cart->items()
                ->where('product_id', $data->productId)
                ->when(
                    $data->variantId,
                    fn($q) => $q->where('variant_id', $data->variantId),
                    fn($q) => $q->whereNull('variant_id')
                )
                ->lockForUpdate()
                ->first();

            $newQuantity = $cartItem
                ? $cartItem->quantity + $data->quantity
                : $data->quantity;

            if ($product->manage_stock && $variant && $variant->stock_quantity < $newQuantity) {
                throw new BusinessException(
                    'موجودی کافی نیست یا کالا در سبد دیگران رزرو شده است.',
                    httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $quantity = $data->quantity;
            if ($cartItem) {
                $cartItem->increment('quantity', $quantity);
            } else {
                $cartItem = $cart->items()->create([
                    'product_id' => $data->productId,
                    'variant_id' => $data->variantId,
                    'quantity' => $quantity,
                    'unit_price_snapshot' => $this->priceResolver->resolve($product, $variant),
                ]);
            }

            $updated = DB::table('carts')
                ->where('id', $cart->id)
                ->where('version', $cart->version)
                ->update([
                    'version' => $cart->version + 1,
                    'last_activity_at' => now(),
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                throw new BusinessException(
                    'سبد خرید شما توسط درخواست دیگری بروزرسانی شده است. لطفاً دوباره تلاش کنید.',
                    httpCode: Response::HTTP_CONFLICT,
                );
            }

            $this->cartCalculator->recalculate($cart);

            return $cartItem;
        });
    }

    private function getOrCreateCart(AddItemToCartData $data): Cart
    {
        $attributes = [
            'status' => CartStatus::Active,
            'type' => CartType::Main,
        ];

        if ($data->userId) {
            $attributes['user_id'] = $data->userId;
        } else {
            $attributes['session_id'] = $data->sessionId;
        }

        return Cart::query()->firstOrCreate($attributes);
    }
}
