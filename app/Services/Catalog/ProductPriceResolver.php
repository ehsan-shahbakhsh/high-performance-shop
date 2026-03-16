<?php

namespace App\Services\Catalog;

use App\Models\{Product, ProductVariant};

class ProductPriceResolver
{
    public function resolve(Product $product, ?ProductVariant $variant = null): int
    {
        if ($variant) {
            return $variant->sale_price ?? $variant->price ?? 0;
        }

        return $product->sale_price ?? $product->price ?? 0;
    }
}
