<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Enums\CartStatus;
use App\Enums\CartType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\Cart\StoreCartRequest;
use App\Http\Resources\V1\Sales\CartResource;
use App\Http\Responses\ApiResponse;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $carts = $user->carts()->latest()->get();

        return ApiResponse::success(CartResource::collection($carts));
    }

    public function store(StoreCartRequest $request)
    {
        $inputs = $request->validated();
        $user = $request->user();

        $cart = $user->carts()->create([
            'type' => CartType::Named,
            'name' => $inputs['name'],
            'status' => CartStatus::Active,
        ]);

        return ApiResponse::created(CartResource::make($cart), 'سبد خرید با موفقیت ایجاد شد.');
    }

    public function destroy(Cart $cart)
    {
        Gate::authorize('delete', $cart);

        $cart->delete();

        return ApiResponse::deleted('سبد خرید و تمامی محصولات آن حذف شدند.');
    }
}
