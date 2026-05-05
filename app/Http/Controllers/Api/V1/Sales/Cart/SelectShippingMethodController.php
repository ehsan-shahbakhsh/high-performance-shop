<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\Cart\SelectShippingMethodRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Cart;
use App\Models\ShippingMethod;
use App\Services\Sales\Shipping\ShippingService;
use Symfony\Component\HttpFoundation\Response;

class SelectShippingMethodController extends Controller
{
    public function __invoke(SelectShippingMethodRequest $request, ShippingService $shippingService)
    {
        $methodId = $request->validated('shipping_method_id');

        $user = $request->user();
        $userCart = $user->mainCart;
        $shippingMethod = ShippingMethod::query()->findOrFail($methodId);

        if (!$shippingService->isSupportedForAddress($shippingMethod, $userCart->shippingAddress, $userCart->total_weight)) {
            return ApiResponse::validationFailed('روش ارسال انتخابی برای آدرس شما پشتیبانی نمی‌شود یا وزن مرسوله بیش از حد مجاز است.');
        }

        $updated = Cart::query()
            ->whereKey($userCart->id)
            ->where('version', $userCart->version)
            ->update([
                'shipping_method_id' => $methodId,
                'version' => $userCart->version + 1,
            ]);
        if (!$updated) {
            return ApiResponse::error('سبد خرید شما تغییر کرده است، لطفا صفحه را بروزرسانی کنید.', Response::HTTP_CONFLICT);
        }

        return ApiResponse::success(message: 'روش ارسال با موفقیت ثبت شد.');
    }
}
