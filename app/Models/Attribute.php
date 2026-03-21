<?php

namespace App\Models;

use App\Enums\AttributeType;
use Database\Factories\AttributeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    /** @use HasFactory<AttributeFactory> */
    use HasFactory;

    protected $fillable = [
        'attribute_group_id',
        'code',
        'name',
        'type',
        'is_filterable',
        'is_required',
        'is_variant',
        'position',
    ];

    protected $casts = [
        'type' => AttributeType::class,
        'is_filterable' => 'bool',
        'is_required' => 'bool',
        'is_variant' => 'bool',
        'position' => 'integer',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class)->orderBy('position');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'attribute_group_id');
    }
}
