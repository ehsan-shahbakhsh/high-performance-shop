<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Actions\Sales\Cart\ApplyCouponAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\Cart\ApplyCouponRequest;
use App\Http\Responses\ApiResponse;
use Throwable;

class ApplyCouponController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(ApplyCouponRequest $request, ApplyCouponAction $action)
    {
        $result = $action->execute($request->user()->mainCart, $request->validated('coupon_code'));

        return ApiResponse::success(['cart_summary' => $result->cartSummary], message: $result->message);
    }
}
