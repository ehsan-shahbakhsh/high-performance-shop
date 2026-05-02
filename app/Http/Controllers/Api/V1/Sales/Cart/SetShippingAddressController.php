<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\Cart\SetShippingAddressRequest;
use App\Http\Responses\ApiResponse;

class SetShippingAddressController extends Controller
{
    public function __invoke(SetShippingAddressRequest $request)
    {
        $addressId = $request->validated('address_id');

        $user = $request->user();
        $user->mainCart()->update(['shipping_address_id' => $addressId]);

        return ApiResponse::success(message: 'آدرس ارسال با موفقیت ثبت شد.');
    }
}
