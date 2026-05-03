<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\Cart\ListShippingMethodsRequest;
use App\Http\Resources\V1\Sales\ShippingMethodResource;
use App\Http\Responses\ApiResponse;
use App\Services\Sales\Shipping\ShippingService;

class ListShippingMethodsController extends Controller
{
    public function __invoke(ListShippingMethodsRequest $request, ShippingService $shippingService)
    {
        $addressId = $request->validated('address_id');

        $user = $request->user();
        $userCart = $user->mainCart;

        if (!$addressId && !$userCart->shipping_address_id) {
            return ApiResponse::validationFailed('برای مشاهده روش‌های ارسال، ابتدا باید آدرس ارسال را مشخص کنید.');
        }

        $addressId ??= $userCart->shipping_address_id;
        $address = $user->addresses()->find($addressId);

        $methods = $shippingService->getAvailableMethods($address, $user->mainCart);

        $methods->each(/**
         * @throws BusinessException
         */ function ($method) use ($shippingService, $userCart, $address) {
            $method->calculated_cost = $shippingService->calculateMethodPrice($method, $userCart, $address, false);

            if ($method->is_cod_supported) {
                $method->calculated_cod_cost = $shippingService->calculateMethodPrice($method, $userCart, $address, true);
                $method->cod_surcharge = $method->calculated_cod_cost - $method->calculated_cost;
            } else {
                $method->calculated_cod_cost = null;
                $method->cod_surcharge = 0;
            }
        });

        return ApiResponse::success(ShippingMethodResource::collection($methods));
    }
}
