<?php

namespace App\Models;

use Database\Factories\AttributeGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttributeGroup extends Model
{
    /** @use HasFactory<AttributeGroupFactory> */
    use HasFactory;

    protected $fillable = ['attribute_set_id', 'name', 'position'];

    public function attributeSet(): BelongsTo
    {
        return $this->belongsTo(AttributeSet::class);
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class)
            ->withPivot('position')
            ->orderByPivot('position');
    }
}
