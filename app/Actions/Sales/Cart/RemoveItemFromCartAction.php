<?php

namespace App\Actions\Sales\Cart;

use App\Exceptions\BusinessException;
use App\Models\{Cart, CartItem};
use App\Services\Sales\Cart\CartCalculator;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

readonly class RemoveItemFromCartAction
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
    public function execute(int $cartItemId, ?string $sessionId = null): void
    {
        if ($sessionId) {
            $this->removeGuestItem($cartItemId, $sessionId);
        } else {
            $this->removeUserItem($cartItemId);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws BusinessException
     */
    private function removeGuestItem(int $variantId, string $sessionId): void
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

        unset($items[$variantId]);
        $redis->setex($redisKey, 14 * 86400, json_encode($items));
    }

    /**
     * @throws Throwable
     */
    private function removeUserItem(int $cartItemId): void
    {
        DB::transaction(function () use ($cartItemId) {
            $cartItem = CartItem::query()
                ->whereKey($cartItemId)
                ->lockForUpdate()
                ->firstOrFail();

            $cart = Cart::query()
                ->whereKey($cartItem->cart_id)
                ->lockForUpdate()
                ->firstOrFail();

            $cartItem->delete();

            $this->cartCalculator->recalculate($cart);
            $this->updateCartVersion($cart);
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
