<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Enums\{CartStatus, CartType};
use App\Models\Cart;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Sales\CartResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Throwable;

class CartController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $type = $request->enum('type', CartType::class, CartType::Main);

            $cart = $user->carts()
                ->firstOrCreate([
                    'status' => CartStatus::Active,
                    'type' => $type,
                ]);
        } else {
            $sessionId = $request->header('Session-Id');

            throw_unless($sessionId, AuthenticationException::class);

            $cart = Cart::query()
                ->firstOrCreate([
                    'session_id' => $sessionId,
                    'status' => CartStatus::Active,
                    'type' => CartType::Main,
                ]);
        }

        $cart->load([
            'items.product.media',
            'items.variant',
        ]);

        return ApiResponse::success(CartResource::make($cart));
    }
}
