<?php

namespace App\Models;

use App\Enums\AttributeType;
use Database\Factories\AttributeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    /** @use HasFactory<AttributeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'is_filterable',
        'is_required',
    ];

    protected $casts = [
        'type' => AttributeType::class,
        'is_filterable' => 'bool',
        'is_required' => 'bool',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class)->orderBy('position');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(AttributeGroup::class)
            ->withPivot('position');
    }
}
