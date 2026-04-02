<?php

namespace App\Actions\Sales\Cart;

use App\Data\Sales\AddItemToCartData;
use App\Enums\{CartType, CartStatus};
use App\Exceptions\BusinessException;
use App\Models\{Cart, CartItem, ProductVariant};
use App\Services\Sales\Cart\CartCalculator;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class AddItemToCartAction
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
    public function execute(AddItemToCartData $data): CartItem
    {
        if ($data->sessionId) {
            return $this->addToGuestCart($data->variantId, $data->sessionId, $data->quantity);
        }

        return $this->addToUserCart($data->variantId, $data->userId, $data->quantity);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BusinessException
     */
    private function addToGuestCart(int $variantId, string $sessionId, int $quantity): CartItem
    {
        $variant = ProductVariant::query()->with('product')->findOrFail($variantId);
        $redis = resolve('redis');

        $redisKey = "cart:$sessionId";
        $items = json_decode($redis->get($redisKey) ?: '[]', true);

        $existingQuantity = $items[$variantId]['quantity'] ?? 0;
        $newQuantity = $existingQuantity + $quantity;

        if ($variant->product->manage_stock && $variant->stock_quantity < $newQuantity) {
            throw new BusinessException(
                'موجودی کافی نیست یا کالا در سبد دیگران رزرو شده است.',
                httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $variantData = [
            'product_variant_id' => $variantId,
            'quantity' => $newQuantity,
            'price_when_added' => $variant->final_price,
        ];

        $items[$variantId] = $variantData;

        $redis->setex($redisKey, 14 * 86400, json_encode($items));

        return new CartItem($variantData);
    }

    /**
     * @throws Throwable
     */
    private function addToUserCart(int $variantId, int $userId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($variantId, $userId, $quantity) {
            $variant = ProductVariant::query()
                ->with('product')
                ->whereKey($variantId)
                ->lockForUpdate()
                ->firstOrFail();

            $cart = Cart::query()
                ->whereKey($this->getOrCreateCart($userId)->id)
                ->lockForUpdate()
                ->firstOr();

            $cartItem = $cart->items()
                ->where('product_variant_id', $variantId)
                ->lockForUpdate()
                ->first();

            $newQuantity = $cartItem
                ? $cartItem->quantity + $quantity
                : $quantity;

            if ($variant->product->manage_stock && $variant->stock_quantity < $newQuantity) {
                throw new BusinessException(
                    'موجودی کافی نیست یا کالا در سبد دیگران رزرو شده است.',
                    httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            if ($cartItem) {
                $cartItem->quantity = $newQuantity;
                $cartItem->save();
            } else {
                $cartItem = $cart->items()->create([
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'price_when_added' => $variant->final_price,
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

    private function getOrCreateCart(int $userId): Cart
    {
        return Cart::query()->firstOrCreate([
            'status' => CartStatus::Active,
            'type' => CartType::Main,
            'user_id' => $userId,
        ]);
    }
}
