<?php

namespace App\Models;

use Database\Factories\VariantAttributeValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;

class VariantAttributeValue extends Model
{
    /** @use HasFactory<VariantAttributeValueFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'attribute_id',
        'attribute_option_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function attributeOption(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class);
    }

    public function displayValue(): CastAttribute
    {
        return new CastAttribute(
            get: fn () => $this->attributeOption->label,
        );
    }
}
