<?php

namespace App\Policies\Sales;

use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Auth\Access\Response;

class WishlistItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WishlistItem $wishlistItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Wishlist $wishlist): bool
    {
        return $user->id === $wishlist->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WishlistItem $wishlistItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WishlistItem $wishlistItem): bool
    {
        return $wishlistItem->wishlist->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WishlistItem $wishlistItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WishlistItem $wishlistItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can move to cart the model.
     */
    public function moveToCart(User $user, WishlistItem $wishlistItem): bool
    {
        return $user->id === $wishlistItem->wishlist->user_id;
    }
}
