<?php

namespace App\Actions\Sales\Cart;

use App\Exceptions\BusinessException;
use App\Models\{Cart, CartItem, ProductVariant};
use App\Services\Sales\Cart\CartCalculator;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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
    public function execute(int $cartItemId, int $quantity, ?string $sessionId = null): ?CartItem
    {
        if ($sessionId) {
            return $this->updateGuestItem($cartItemId, $quantity, $sessionId);
        }

        return $this->updateUserItem($cartItemId, $quantity);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BusinessException
     */
    private function updateGuestItem(int $variantId, int $quantity, string $sessionId): ?CartItem
    {
        $redis = resolve('redis');

        $redisKey = "cart:$sessionId";
        $items = json_decode($redis->get($redisKey) ?: '[]', true);

        if (!isset($items[$variantId])) {
            throw new BusinessException(
                'آیتم در سبد خرید یافت نشد.',
                httpCode: Response::HTTP_NOT_FOUND,
            );
        }

        if ($quantity <= 0) {
            unset($items[$variantId]);
            $redis->setex($redisKey, 14 * 86400, json_encode($items));
            return null;
        }

        $variant = ProductVariant::query()->with('product')->findOrFail($variantId);
        $product = $variant->product;

        if ($product->manage_stock && $variant->stock_quantity < $quantity) {
            throw new BusinessException(
                'موجودی کافی نیست یا کالا در سبد دیگران رزرو شده است.',
                httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $items[$variantId]['quantity'] = $quantity;

        $redis->setex($redisKey, 14 * 86400, json_encode($items));

        return new CartItem($items[$variantId]);
    }

    /**
     * @throws Throwable
     */
    private function updateUserItem(int $cartItemId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($cartItemId, $quantity) {
            $cartItem = CartItem::query()
                ->with('variant.product.media')
                ->whereKey($cartItemId)
                ->lockForUpdate()
                ->firstOrFail();

            $cart = Cart::query()
                ->whereKey($cartItem->cart_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($quantity <= 0) {
                $cartItem->delete();
                $this->cartCalculator->recalculate($cart);
                $this->updateCartVersion($cart);
                return $cartItem;
            }

            $variant = $cartItem->variant;
            $product = $variant->product;

            if ($product->manage_stock && $variant->stock_quantity < $quantity) {
                throw new BusinessException(
                    'موجودی کافی نیست یا کالا در سبد دیگران رزرو شده است.',
                    httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            $cartItem->update(['quantity' => $quantity]);

            $this->cartCalculator->recalculate($cart);
            $this->updateCartVersion($cart);

            return $cartItem;
        });
    }

    /**
     * @throws BusinessException
     */
    private function updateCartVersion(Cart $cart): void
    {
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
    }
}
