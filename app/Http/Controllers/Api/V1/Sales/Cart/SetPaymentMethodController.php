<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\Cart\SetPaymentMethodRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Cart;
use Symfony\Component\HttpFoundation\Response;

class SetPaymentMethodController extends Controller
{
    public function __invoke(SetPaymentMethodRequest $request)
    {
        $methodId = $request->validated('payment_method_id');

        $user = $request->user();
        $userCart = $user->mainCart;

        $updated = Cart::query()
            ->whereKey($userCart->id)
            ->where('version', $userCart->version)
            ->update([
                'payment_method_id' => $methodId,
                'version' => $userCart->version + 1,
            ]);
        if (!$updated) {
            return ApiResponse::error('سبد خرید شما تغییر کرده است، لطفا صفحه را بروزرسانی کنید.', Response::HTTP_CONFLICT);
        }

        return ApiResponse::success(message: 'روش پرداخت با موفقیت ثبت شد.');
    }
}
