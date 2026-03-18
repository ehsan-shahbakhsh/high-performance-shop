<?php

namespace App\Actions\Sales\Wishlist;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CreateWishlistAction
{
    /**
     * @throws Throwable
     */
    public function execute(User $user, string $name): Wishlist
    {
        return DB::transaction(function () use ($user, $name) {
            $count = $user->wishlists()->lockForUpdate()->count();

            if ($count >= config('commerce.wishlist.max_per_user', 20)) {
                throw new BusinessException(
                    'حداکثر ۲۰ لیست علاقه‌مندی می‌توانید داشته باشید.',
                    httpCode: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            return $user->wishlists()->create([
                'name' => $name,
                'is_default' => false,
            ]);
        });
    }
}
