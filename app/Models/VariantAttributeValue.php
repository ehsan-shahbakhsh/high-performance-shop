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
        'value_string',
        'value_number',
        'value_boolean',
        'value_date',
    ];

    protected $casts = [
        'value_boolean' => 'boolean',
        'value_date' => 'date',
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
            get: function () {
                if ($this->attribute_option_id) {
                    return $this->attributeOption->label;
                }

                if ($this->value_date) {
                    return verta($this->value_date)->format('Y/m/d');
                }

                return $this->value_text
                    ?? $this->value_number
                    ?? ($this->value_boolean ? 'بله' : 'خیر');
            },
        );
    }
}
