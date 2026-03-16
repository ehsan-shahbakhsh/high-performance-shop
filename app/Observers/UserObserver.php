<?php

namespace App\Observers;

use App\Enums\{CartStatus, CartType};
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserObserver
{
    /**
     * Handle the User "created" event.
     * @throws Throwable
     */
    public function created(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->wishlists()->create([
                'name' => config('commerce.wishlist.default_name'),
                'is_default' => true,
            ]);
            $user->carts()->createManyQuietly([
                [
                    'type' => CartType::Main,
                    'status' => CartStatus::Active,
                ],
                [
                    'type' => CartType::Secondary,
                    'status' => CartStatus::Active,
                ],
            ]);
        });
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
