<?php

namespace App\Models;

use App\Enums\ProductOutOfStockAction;
use App\Enums\ProductRelationType;
use App\Enums\ProductType;
use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;
    /** @use HasFactory<ProductFactory> */
    use HasFactory;
    use SoftDeletes;
    use Sluggable;

    protected $fillable = [
        'attribute_set_id',
        'brand_id',
        'type',
        'sku',
        'name',
        'slug',
        'price',
        'sale_price',
        'is_active',
        'manage_stock',
        'out_of_stock_action',
        'custom_stock_text',
        'attributes',
        'short_description',
        'description',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'type' => ProductType::class,
        'is_active' => 'boolean',
        'manage_stock' => 'boolean',
        'attributes' => 'json',
        'out_of_stock_action' => ProductOutOfStockAction::class,
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->useDisk('public')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg','image/png','image/webp'])
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->fit(Fit::Crop, 400, 400)
                    ->format('webp')
                    ->quality(80);
            });

        $this->addMediaCollection('gallery')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg','image/png','image/webp'])
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->fit(Fit::Crop, 300, 300)
                    ->format('webp')
                    ->quality(80)
                    ->sharpen(10);

                $this->addMediaConversion('large')
                    ->width(1200)
                    ->format('webp')
                    ->quality(85);
            });

        $this->addMediaCollection('videos')
            ->useDisk('public')
            ->acceptsMimeTypes(['video/mp4','video/webm']);
    }

    public function attributeSet(): BelongsTo
    {
        return $this->belongsTo(AttributeSet::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function isVariable(): bool
    {
        return $this->type === ProductType::Variable;
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProductFile::class)->orderBy('position');
    }

    public function isDownloadable(): bool
    {
        return $this->type === ProductType::Downloadable;
    }

    public function productRelations(): HasMany
    {
        return $this->hasMany(ProductRelation::class, 'product_id');
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_relations', 'product_id', 'related_product_id')
            ->withPivot(['type', 'position'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    public function upsells(): BelongsToMany
    {
        return $this->relatedProducts()
            ->wherePivot('type', ProductRelationType::Upsell);
    }

    public function crossSells(): BelongsToMany
    {
        return $this->relatedProducts()
            ->wherePivot('type', ProductRelationType::CrossSell);
    }

    public function similarProducts(): BelongsToMany
    {
        return $this->relatedProducts()
            ->wherePivot('type', ProductRelationType::Related);
    }

    public function bundleItems(): BelongsToMany
    {
        return $this->relatedProducts()
            ->wherePivot('type', ProductRelationType::Bundle);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }
}
