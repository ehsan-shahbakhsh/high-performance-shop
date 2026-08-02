<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Sales\CartSummaryResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

class CartSummaryController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $userCart = $user->mainCart;

        $userCart->load([
            'items.variant.product.media',
            'items.variant.media',
            'shippingMethod',
            'shippingAddress',
            'paymentMethod',
            'coupon',
        ]);

        return ApiResponse::success(
            data: CartSummaryResource::make($userCart),
            message: 'خلاصه سبد خرید با موفقیت دریافت شد.',
        );
    }
}
