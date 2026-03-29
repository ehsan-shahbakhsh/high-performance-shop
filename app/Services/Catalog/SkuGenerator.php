<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SkuGenerator
{
    /**
     * @param Product $product
     * @param array<int, array{code: string, type: string, value: mixed}> $attributes
     * @param int|null $ignore
     * @return string
     */
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

    /**
     * @param array<int, array{code: string, type: string, value: mixed}> $attributes
     * @return string
     */
    protected function attributeCode(array $attributes): string
    {
        if (empty($attributes)) {
            return '';
        }

        return collect($attributes)
            ->map(fn($attribute) => $this->attributeValue(
                $attribute['code'],
                $attribute['type'],
                $attribute['value'],
            ))
            ->join('-');
    }

    protected function attributeValue(string $attributeCode, string $attributeType, mixed $attributeValue): string
    {
        if ($attributeType === 'number') {
            $value = 'N' . $this->normalizeNumber($attributeValue);
        } else if ($attributeType === 'boolean') {
            $value = $attributeValue ? 'YES' : 'NO';
        } else if ($attributeType === 'date') {
            $value = 'D' . verta($attributeValue)->format('ymd');
        } else {
            $value = Str::upper(Str::substr($attributeValue, 0, 3));
        }

        return sprintf(
            '%s-%s',
            Str::upper(Str::substr($attributeCode, 0, 3)),
            $value,
        );
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

    protected function normalizeNumber($value): string
    {
        $v = rtrim(rtrim((string)$value, '0'), '.');
        return substr($v, 0, 6);
    }
}
