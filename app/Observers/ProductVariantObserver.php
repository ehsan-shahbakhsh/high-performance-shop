<?php

namespace App\Observers;

use App\Exceptions\BusinessException;
use App\Models\ProductVariant;

class ProductVariantObserver
{
    /**
     * Handle the ProductVariant "created" event.
     */
    public function created(ProductVariant $productVariant): void
    {
        //
    }

    /**
     * Handle the ProductVariant "updated" event.
     */
    public function updated(ProductVariant $productVariant): void
    {
        //
    }

    /**
     * Handle the ProductVariant "deleted" event.
     */
    public function deleted(ProductVariant $productVariant): void
    {
        $this->updateProductPrices($productVariant);
    }

    /**
     * Handle the ProductVariant "restored" event.
     */
    public function restored(ProductVariant $productVariant): void
    {
        $this->updateProductPrices($productVariant);
    }

    /**
     * Handle the ProductVariant "force deleted" event.
     */
    public function forceDeleted(ProductVariant $productVariant): void
    {
        //
    }

    /**
     * Handle the ProductVariant "saving" event.
     * @throws BusinessException
     */
    public function saving(ProductVariant $productVariant): void
    {
        if ($productVariant->isDirty('is_default')) {

            $old = $productVariant->getOriginal('is_default');
            $new = $productVariant->is_default;

            if ($old === true && $new === false) {
                throw new BusinessException('برای تغییر پیش‌فرض، ابتدا باید یک تنوع دیگر را به عنوان پیش‌فرض انتخاب کنید.');
            }

            if ($new === true) {
                $productVariant->product
                    ->variants()
                    ->whereKeyNot($productVariant->id)
                    ->update(['is_default' => false]);
            }
        }
    }

    /**
     * Handle the ProductVariant "saved" event.
     */
    public function saved(ProductVariant $productVariant): void
    {
        $this->updateProductPrices($productVariant);
    }

    protected function updateProductPrices(ProductVariant $variant): void
    {
        $product = $variant->product;

        $variants = $product->variants();

        $product->update([
            'min_price' => $variants->min('price'),
            'max_price' => $variants->max('price'),
            'min_sale_price' => $variants->whereNotNull('sale_price')->min('sale_price'),
            'max_sale_price' => $variants->whereNotNull('sale_price')->max('sale_price'),
        ]);
    }
}
