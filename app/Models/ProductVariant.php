<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Spatie\MediaLibrary\{HasMedia, InteractsWithMedia, MediaCollections\Models\Media};
use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;
use Spatie\Image\Enums\Fit;

class ProductVariant extends Model implements HasMedia
{
    use InteractsWithMedia;
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'price',
        'sale_price',
        'sale_start',
        'sale_end',
        'stock_quantity',
        'sku',
        'is_default',
        'is_active',
        'position',
        'weight',
        'length',
        'width',
        'height',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'price' => 'integer',
        'sale_price' => 'integer',
        'position' => 'integer',
        'sale_start' => 'datetime',
        'sale_end' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('variant_gallery')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        if (!$media || !str_starts_with($media->mime_type, 'image/')) {
            return;
        }

        // thumbnail (admin tables / selects)
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 300, 300)
            ->format('webp')
            ->quality(80)
            ->queued()
            ->performOnCollections('variant_gallery');

        // product cards
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 500, 500)
            ->format('webp')
            ->quality(85)
            ->queued()
            ->performOnCollections('variant_gallery');

        // gallery (product page)
        $this->addMediaConversion('gallery')
            ->width(800)
            ->format('webp')
            ->quality(85)
            ->queued()
            ->performOnCollections('variant_gallery');

        // zoom image
        $this->addMediaConversion('zoom')
            ->width(1600)
            ->format('webp')
            ->quality(90)
            ->queued()
            ->performOnCollections('variant_gallery');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function finalPrice(): CastAttribute
    {
        return CastAttribute::make(
            get: fn() => $this->sale_price ?? $this->price,
        );
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProductVariantFile::class);
    }
}
