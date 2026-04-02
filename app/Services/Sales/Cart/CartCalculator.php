<?php

namespace App\Services\Sales\Cart;

use App\Models\Cart;

class CartCalculator
{
    public function recalculate(Cart $cart): void
    {
        $totals = $cart->items()
            ->selectRaw('
                COUNT(*) as items_count,
                SUM(quantity) as items_qty_sum,
                SUM(price_when_added * quantity) as subtotal
            ')
            ->first(); // todo: check if need real-time price of variant

        $subtotal = (int) ($totals->subtotal ?? 0);
        $discount = 0; // TODO
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
