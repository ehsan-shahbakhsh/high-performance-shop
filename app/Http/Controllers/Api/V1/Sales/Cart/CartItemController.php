<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Actions\Sales\Cart\{AddItemToCartAction, MoveCartItemAction, RemoveItemFromCartAction, UpdateCartItemAction};
use App\Data\Sales\AddItemToCartData;
use App\Models\Cart;
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
    public function update(UpdateCartItemRequest $request, CartItem $item, UpdateCartItemAction $action)
    {
        $sessionId = $request->header('Session-Id');

        Gate::authorize('update', [$item, $sessionId]);

        $quantity = intval($request->validated('quantity'));

        $item = $action->execute($item, $quantity);

        $message = $quantity > 0
            ? 'تعداد محصول در سبد خرید بروزرسانی شد.'
            : 'محصول از سبد خرید حذف شد.';
        return ApiResponse::success(CartItemResource::make($item), $message);
    }

    /**
     * @throws Throwable
     */
    public function destroy(Request $request, CartItem $item, RemoveItemFromCartAction $action)
    {
        Gate::authorize('delete', [$item, $request->header('Session-Id')]);

        $action->execute($request->user(), $item);

        return ApiResponse::success(message: 'محصول با موفقیت از سبد خرید حذف شد.');
    }

    /**
     * @throws Throwable
     */
    public function move(MoveCartItemRequest $request, Cart $cart, CartItem $item, MoveCartItemAction $action)
    {
        Gate::authorize('move', $item);

        $targetType = CartType::from($request->validated('target_type'));

        $action->execute(
            $request->user(),
            $item,
            $targetType,
        );

        $message = match ($targetType) {
            CartType::Main => 'محصول با موفقیت به سبد خرید اصلی منتقل شد.',
            CartType::Secondary => 'محصول با موفقیت به سبد خرید بعدی منتقل شد.',
        };
        return ApiResponse::success(message: $message);
    }
}
