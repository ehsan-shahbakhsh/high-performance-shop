<?php

namespace App\Listeners\Auth;

use App\Events\Auth\UserAuthenticated;
use App\Services\Sales\Cart\CartCalculator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class TransferGuestCart implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(private readonly CartCalculator $cartCalculator)
    {
    }

    /**
     * Handle the event.
     * @throws Throwable
     */
    public function handle(UserAuthenticated $event): void
    {
        if (!$event->sessionId) return;

        $redisKey = "cart:{$event->sessionId}";
        $guestCart = Redis::get($redisKey);
        if (!$guestCart) return;

        $guestItems = json_decode($guestCart, true);
        if (blank($guestItems)) return;

        DB::transaction(function () use ($event, $guestItems) {
            $userCart = $event->user->mainCart()->lockForUpdate()->first();

            foreach ($guestItems as $item) {
                $cartItem = $userCart->items()
                    ->where('product_variant_id', $item['product_variant_id'])
                    ->lockForUpdate()
                    ->first();

                if ($cartItem) {
                    $cartItem->quantity += $item['quantity'];
                    $cartItem->price_when_added = $item['price_when_added'];
                    $cartItem->save();
                } else {
                    $userCart->items()->create($item);
                }
            }

            $this->cartCalculator->recalculate($userCart);
        });

        Redis::del($redisKey);
    }
}
