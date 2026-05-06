<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\Cart\SetShippingAddressRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Cart;
use Symfony\Component\HttpFoundation\Response;

class SetShippingAddressController extends Controller
{
    public function __invoke(SetShippingAddressRequest $request)
    {
        $newAddressId = $request->validated('address_id');

        $user = $request->user();
        $userCart = $user->mainCart;

        if ($userCart->shipping_address_id == $newAddressId) {
            return ApiResponse::success(message: 'آدرس از قبل روی این سبد خرید تنظیم شده است.');
        }

        $updated = Cart::query()
            ->whereKey($userCart->id)
            ->where('version', $userCart->version)
            ->update([
                'shipping_address_id' => $newAddressId,
                'shipping_method_id' => null,
                'version' => $userCart->version + 1,
            ]);

        if (!$updated) {
            return ApiResponse::error('سبد خرید شما تغییر کرده است، لطفا صفحه را بروزرسانی کنید.', Response::HTTP_CONFLICT);
        }

        return ApiResponse::success(message: 'آدرس ارسال با موفقیت ثبت شد.');
    }
}
