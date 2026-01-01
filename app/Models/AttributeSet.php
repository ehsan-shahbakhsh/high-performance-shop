<?php

namespace App\Models;

use Database\Factories\AttributeSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeSet extends Model
{
    /** @use HasFactory<AttributeSetFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(AttributeGroup::class)->orderBy('position');
    }
}
