<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;

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
        'stock_quantity',
        'sku',
        'attributes',
        'variant_hash',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'json',
        'stock_quantity' => 'integer',
        'is_active' => 'boolean',
        'price' => 'decimal:4',
        'sale_price' => 'decimal:4',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->useDisk('public')
            ->registerMediaConversions(function ($media) {
                $this->addMediaConversion('thumb')
                    ->width(200)
                    ->height(200);
            });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function finalPrice(): CastAttribute
    {
        return CastAttribute::make(
            get: fn () => $this->price ?? $this->product->price
        );
    }

    protected static function booted(): void
    {
        static::saving(function (self $variant) {
            if ($variant->isDirty('attributes') || empty($variant->variant_hash)) {
                $variant->variant_hash = self::generateHash($variant->attributes);
            }
        });
    }

    public static function generateHash(array $attributes): string
    {
        ksort($attributes);
        $json = json_encode($attributes);
        return md5($json);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
