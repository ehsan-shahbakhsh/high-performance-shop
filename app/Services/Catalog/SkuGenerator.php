<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SkuGenerator
{
    public function generate(Product $product, array $attributes = [], ?int $ignore = null): string
    {
        $productCode = $this->productCode($product);

        $attributeCode = $this->attributeCode($attributes);

        $base = trim($productCode . '-' . $attributeCode, '-');

        return $this->ensureUnique($base, $ignore);
    }

    protected function productCode(Product $product): string
    {
        return Str::of($product->slug)
            ->replace('-', '')
            ->upper()
            ->substr(0, 5);
    }

    protected function attributeCode(array $attributes): string
    {
        if (empty($attributes)) {
            return '';
        }

        return collect($attributes)
            ->map(function ($value) {
                return Str::upper(Str::substr($value, 0, 3));
            })
            ->join('-');
    }

    protected function ensureUnique(string $sku, ?int $ignore = null): string
    {
        $original = $sku;
        $counter = 1;

        $query = ProductVariant::query()->when($ignore, fn(Builder $query) => $query->whereKeyNot($ignore));


        while ($query->where('sku', $sku)->exists()) {
            $sku = $original . '-' . $counter;
            $counter++;
        }

        return $sku;
    }
}
