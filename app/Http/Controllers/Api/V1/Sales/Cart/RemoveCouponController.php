<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Actions\Sales\Cart\RemoveCouponAction;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Throwable;

class RemoveCouponController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(Request $request, RemoveCouponAction $action)
    {
        $action->execute($request->user()->mainCart);

        return ApiResponse::success(message: 'کد تخفیف از سبد خرید حذف شد.');
    }
}
