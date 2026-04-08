<?php

namespace App\Http\Controllers\Api\V1\Sales\Cart;

use App\Models\CartItem;
use App\Models\ProductVariant;
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

            $cart->load([
                'items.variant.product.media',
                'items.variant.media',
            ]);
        } else {
            $sessionId = $request->header('Session-Id');

            throw_unless($sessionId, AuthenticationException::class);

            $items = json_decode(resolve('redis')->get("cart:$sessionId") ?: '[]', true);

            $cart = new Cart;
            $cart->id = $sessionId;
            $cart->user_id = null;
            $cart->type = CartType::Main;
            $cart->status = CartStatus::Active;
            $cart->items_count = count($items);
            $cart->items_qty_sum = 0;
            $cart->subtotal = 0;
            $cart->discount_total = 0;
            $cart->shipping_total = 0; // TODO
            $cart->total = 0;
            $cart->version = 0;

            $cartItems = collect();
            foreach ($items as $item) {
                $variant = ProductVariant::query()->with('product.media')->find($item['product_variant_id']);

                if ($variant) {
                    $cart->items_qty_sum += $item['quantity'];
                    $cart->subtotal += $variant->price;
                    $cart->discount_total += ($variant->price - $variant->final_price) * $item['quantity'];

                    $cartItem = new CartItem([
                        'id' => $variant->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $item['quantity'],
                        'price_when_added' => $variant->final_price,
                    ]);
                    $cartItem->setRelation('variant', $variant);

                    $cartItems->add($cartItem);
                }
            }

            $cart->total = $cart->subtotal - $cart->discount_total + $cart->shipping_total;

            $cart->setRelation('items', $cartItems);
        }

        return ApiResponse::success(CartResource::make($cart));
    }
}
