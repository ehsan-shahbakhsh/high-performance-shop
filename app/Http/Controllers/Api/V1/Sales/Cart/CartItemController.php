<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Actions\Sales\Cart\{AddItemToCartAction, MoveCartItemAction, RemoveItemFromCartAction, UpdateCartItemAction};
use App\Data\Sales\AddItemToCartData;
use App\Enums\{CartStatus, CartType};
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\Cart\{MoveCartItemRequest, StoreCartItemRequest, UpdateCartItemRequest};
use App\Http\Resources\V1\Sales\{CartResource, CartItemResource};
use App\Http\Responses\ApiResponse;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

class CartItemController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cart = $user->carts()
            ->with([
                'items.product.media',
                'items.variant.media',
            ])
            ->firstOrCreate([
                'status' => CartStatus::Active,
                'type' => CartType::Main,
            ]);

        return ApiResponse::success(CartResource::make($cart));
    }

    /**
     * @throws Throwable
     */
    public function store(StoreCartItemRequest $request, AddItemToCartAction $action)
    {
        $inputs = $request->validated();

        $cartItem = $action->execute(AddItemToCartData::validateAndCreate([
            'user_id' => $request->user()?->id,
            'session_id' => $request->header('Session-Id'),
            'product_id' => $inputs['product_id'],
            'variant_id' => $inputs['variant_id'] ?? null,
            'quantity' => intval($inputs['quantity']),
        ]));

        return ApiResponse::success(CartItemResource::make($cartItem), 'کالا با موفقیت به سبد خرید اضافه شد.');
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateCartItemRequest $request, CartItem $cartItem, UpdateCartItemAction $action)
    {
        $sessionId = $request->header('Session-Id');

        Gate::authorize('update', [$cartItem, $sessionId]);

        $quantity = intval($request->validated('quantity'));

        $cartItem = $action->execute($cartItem, $quantity);

        $message = $quantity > 0
            ? 'تعداد محصول در سبد خرید بروزرسانی شد.'
            : 'محصول از سبد خرید حذف شد.';
        return ApiResponse::success(CartItemResource::make($cartItem), $message);
    }

    /**
     * @throws Throwable
     */
    public function destroy(Request $request, CartItem $cartItem, RemoveItemFromCartAction $action)
    {
        Gate::authorize('delete', [$cartItem, $request->header('Session-Id')]);

        $action->execute($request->user(), $cartItem);

        return ApiResponse::success(message: 'محصول با موفقیت از سبد خرید حذف شد.');
    }

    /**
     * @throws Throwable
     */
    public function move(MoveCartItemRequest $request, CartItem $cartItem, MoveCartItemAction $action)
    {
        Gate::authorize('move', $cartItem);

        $inputs = $request->validated();

        $action->execute(
            $request->user(),
            $cartItem,
            $inputs['destination_cart_id'] ?? null,
        );

        return ApiResponse::success(message: 'محصول با موفقیت انتقال یافت.');
    }
}
