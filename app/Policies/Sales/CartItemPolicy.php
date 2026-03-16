<?php

namespace App\Policies\Sales;

use App\Enums\CartStatus;
use App\Models\CartItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CartItemPolicy
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
    public function view(?User $user, CartItem $cartItem, ?string $sessionId): bool
    {
        if ($user) {
            return $user->id === $cartItem->cart->user_id;
        }

        return $sessionId && $sessionId === $cartItem->cart->session_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user, CartItem $cartItem, ?string $sessionId): bool
    {
        if (!$this->canModifyCart($cartItem)) {
            return false;
        }

        if ($user) {
            return $user->id === $cartItem->cart->user_id;
        }

        return $sessionId !== null && $sessionId === $cartItem->cart->session_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user, CartItem $cartItem, ?string $sessionId): bool
    {
        if (!$this->canModifyCart($cartItem)) {
            return false;
        }

        if ($user) {
            return $user->id === $cartItem->cart->user_id;
        }

        return $sessionId !== null && $sessionId === $cartItem->cart->session_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CartItem $cartItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CartItem $cartItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can move the model.
     */
    public function move(User $user, CartItem $cartItem): bool
    {
        return $this->canModifyCart($cartItem) && $cartItem->cart->user_id === $user->id;
    }

    protected function canModifyCart(CartItem $cartItem): bool
    {
        return $cartItem->cart->status === CartStatus::Active
            && !$cartItem->cart->isLocked();
    }
}
