<?php

namespace App\Services\Sales\Cart;

use App\Models\Cart;

class CartCalculator
{
    public function recalculate(Cart $cart): void
    {
        $totals = $cart->items()
            ->join('product_variants as v', 'v.id', '=', 'cart_items.product_variant_id')
            ->selectRaw('
                COUNT(*) as items_count,
                SUM(cart_items.quantity) as items_qty_sum,
                SUM(v.price * cart_items.quantity) as subtotal,
                SUM((v.price - COALESCE(v.sale_price, v.price)) * cart_items.quantity) as discount_total
            ')
            ->first();

        $subtotal = (int)($totals->subtotal ?? 0);
        $discount = (int)($totals->discount_total ?? 0);
        $shipping = 0; // TODO

        $total = $subtotal - $discount + $shipping;

        $cart->update([
            'items_count' => $totals->items_count ?? 0,
            'items_qty_sum' => $totals->items_qty_sum ?? 0,
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'shipping_total' => $shipping,
            'total' => $total,
        ]);
    }
}
