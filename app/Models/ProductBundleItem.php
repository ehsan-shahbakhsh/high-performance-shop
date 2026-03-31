<?php

namespace App\Models;

use App\Enums\ProductBundleItemModifierType;
use Database\Factories\ProductBundleItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBundleItem extends Model
{
    /** @use HasFactory<ProductBundleItemFactory> */
    use HasFactory;

    protected $fillable = [
        'parent_variant_id',
        'child_variant_id',
        'quantity',
        'is_required',
        'modifier_type',
        'price_modifier',
        'position',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_required' => 'boolean',
        'modifier_type' => ProductBundleItemModifierType::class,
        'price_modifier' => 'integer',
        'position' => 'integer',
    ];

    public function parentVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'parent_variant_id');
    }

    public function childVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'child_variant_id');
    }
}
