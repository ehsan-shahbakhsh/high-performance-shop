<?php

namespace App\Models;

use Database\Factories\ProductAttributeMultiValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeMultiValue extends Model
{
    /** @use HasFactory<ProductAttributeMultiValueFactory> */
    use HasFactory;

    protected $fillable = ['product_id', 'attribute_id', 'attribute_option_id'];

    const UPDATED_AT = null;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function attributeOption(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class);
    }
}
