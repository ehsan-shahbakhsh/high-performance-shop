<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Actions\Sales\Cart\{AddItemToCartAction, MoveCartItemAction, RemoveItemFromCartAction, UpdateCartItemAction};
use App\Data\Sales\AddItemToCartData;
use App\Models\Cart;
use App\Enums\CartType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\Cart\{MoveCartItemRequest, StoreCartItemRequest, UpdateCartItemRequest};
use App\Http\Resources\V1\Sales\CartItemResource;
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
            'variant_id' => $inputs['variant_id'],
            'quantity' => intval($inputs['quantity']),
        ]));

        // todo: check need to load variant and product or not

        return ApiResponse::success(CartItemResource::make($cartItem), 'کالا با موفقیت به سبد خرید اضافه شد.');
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateCartItemRequest $request, int $itemId, UpdateCartItemAction $action)
    {
        $sessionId = $request->header('Session-Id');

        if ($request->user()) Gate::authorize('update', CartItem::query()->findOrFail($itemId));

        $quantity = intval($request->validated('quantity'));
        $item = $action->execute($itemId, $quantity, $sessionId);

        if ($quantity > 0) {
            return ApiResponse::success(CartItemResource::make($item), 'تعداد محصول در سبد خرید بروزرسانی شد.');
        }

        return ApiResponse::deleted('محصول از سبد خرید حذف شد.');
    }

    /**
     * @throws Throwable
     */
    public function destroy(Request $request, int $itemId, RemoveItemFromCartAction $action)
    {
        $user = $request->user();
        $sessionId = $request->header('Session-Id');

        if (!$user && !$sessionId) {
            return ApiResponse::unauthorized('برای این عملیات باید وارد حساب کاربری شوید یا شناسه نشست (Session-Id) معتبر ارسال کنید.');
        }

        if ($user) Gate::authorize('delete', CartItem::query()->findOrFail($itemId));

        $action->execute($itemId, $sessionId);

        return ApiResponse::deleted('محصول با موفقیت از سبد خرید حذف شد.');
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
