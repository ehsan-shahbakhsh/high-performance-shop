<?php

namespace App\Models;

use App\Traits\RestoreOrFail;
use Illuminate\Database\LazyLoadingViolationException;
use App\Enums\{AttributeType, ProductOutOfStockAction, ProductStatus, ProductRelationType, ProductType};
use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{HasOne, HasMany, BelongsToMany, BelongsTo};
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\{HasMedia, InteractsWithMedia, MediaCollections\Models\Media};
use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    /** @use HasFactory<ProductFactory> */
    use HasFactory;
    use SoftDeletes;
    use Sluggable;
    use RestoreOrFail;

    protected $fillable = [
        'brand_id',
        'type',
        'status',
        'name',
        'slug',
        'is_active',
        'is_virtual',
        'is_downloadable',
        'manage_stock',
        'out_of_stock_action',
        'custom_stock_text',
        'short_description',
        'description',
        'seo_title',
        'seo_description',
        'published_at',
        'min_price',
        'max_price',
        'min_sale_price',
        'max_sale_price',
    ];

    protected $casts = [
        'type' => ProductType::class,
        'status' => ProductStatus::class,
        'is_active' => 'boolean',
        'is_virtual' => 'boolean',
        'is_downloadable' => 'boolean',
        'manage_stock' => 'boolean',
        'out_of_stock_action' => ProductOutOfStockAction::class,
        'published_at' => 'datetime',
        'min_price' => 'integer',
        'max_price' => 'integer',
        'min_sale_price' => 'integer',
        'max_sale_price' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product_gallery')
            ->useDisk('media')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('product_videos')
            ->useDisk('media')
            ->acceptsMimeTypes(['video/mp4', 'video/webm']);

        $this->addMediaCollection('product_content')
            ->useDisk('media')
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
            ->performOnCollections('product_gallery');

        // product cards
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 500, 500)
            ->format('webp')
            ->quality(85)
            ->queued()
            ->performOnCollections('product_gallery');

        // gallery (product page)
        $this->addMediaConversion('gallery')
            ->width(800)
            ->format('webp')
            ->quality(85)
            ->queued()
            ->performOnCollections('product_gallery');

        // zoom image
        $this->addMediaConversion('zoom')
            ->width(1600)
            ->format('webp')
            ->quality(90)
            ->queued()
            ->performOnCollections('product_gallery');
    }

    public function thumbnailUrl(): CastAttribute
    {
        return new CastAttribute(
            get: function () {
                if (!$this->relationLoaded('media')) {
                    throw new LazyLoadingViolationException($this, 'media');
                }

                return $this->getFirstMediaUrl('product_gallery', 'thumb');
            },
        );
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function isVariable(): bool
    {
        return $this->type === ProductType::Variable;
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

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function attributeMultiValues(): HasMany
    {
        return $this->hasMany(ProductAttributeMultiValue::class);
    }

    public function definedAttributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')
            ->orderByPivot('position');
    }

    public function getAttributeValuesForSku(): array
    {
        $this->loadMissing([
            'attributeValues.attribute',
            'attributeValues.attributeOption',
        ]);

        $result = [];

        foreach ($this->attributeValues as $value) {
            // select
            if ($value?->attribute_option_id) {
                $result[] = [
                    'code' => $value->attribute->code,
                    'value' => $value->attributeOption->value,
                    'type' => $value->attribute->type->value,
                ];
                continue;
            }

            if (filled($value->value_boolean)) {
                $value->value_boolean = boolval($value->value_boolean);
            } else if (filled($value->value_number)) {
                $value->value_number = intval($value->value_number);
            }

            // primitive value
            $result[] = [
                'code' => $value->attribute->code,
                'value' => $value->value_text ??
                        $value->value_number ??
                        $value->value_boolean ??
                        $value->value_date,
                'type' => $value->attribute->type->value,
            ];
        }

        return $result;
    }

    public function displayAttributes(): CastAttribute
    {
        return new CastAttribute(
            get: function (): array {
                $this->loadMissing([
                    'definedAttributes',
                    'attributeValues.attributeOption',
                    'attributeMultiValues.attributeOption',
                ]);

                $result = [];

                foreach ($this->definedAttributes as $attribute) {
                    if ($attribute->type === AttributeType::MultiSelect) {
                        $values = $this->attributeMultiValues->where('attribute_id', $attribute->id);
                        $result[] = [
                            'attribute_name' => $attribute->name,
                            'display_value' => $values->map(static fn($value) => $value->attributeOption->label)->join('، '),
                        ];
                        continue;
                    }

                    $value = $this->attributeValues->firstWhere('attribute_id', $attribute->id);

                    if ($attribute->type === AttributeType::Select) {
                        $result[] = [
                            'attribute_name' => $attribute->name,
                            'display_value' => $value->attributeOption->label,
                        ];
                    } else if ($attribute->type === AttributeType::Date) {
                        $result[] = [
                            'attribute_name' => $attribute->name,
                            'display_value' => verta($this->value_date)->format('Y/m/d'),
                        ];
                    } else {
                        $result[] = [
                            'attribute_name' => $attribute->name,
                            'display_value' => $value->value_string
                                ?? $value->value_text
                                    ?? $value->value_number
                                    ?? ($value->value_boolean ? 'بله' : 'خیر'),
                        ];
                    }
                }

                return $result;
            },
        );
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->status !== ProductStatus::Published) {
            return false;
        }

        if ($this->published_at !== null && $this->published_at->isFuture()) {
            return false;
        }

        return true;
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
