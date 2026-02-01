<?php

namespace App\Models;

use App\Enums\ProductRelationType;
use Database\Factories\ProductRelationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRelation extends Model
{
    /** @use HasFactory<ProductRelationFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'related_product_id',
        'type',
        'position',
    ];

    protected $casts = [
        'type' => ProductRelationType::class,
    ];

    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }
}
