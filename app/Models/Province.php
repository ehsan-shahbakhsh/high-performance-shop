<?php

namespace App\Models;

use Database\Factories\ProvinceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    /** @use HasFactory<ProvinceFactory> */
    use HasFactory;

    protected $fillable = ['name', 'name_en', 'slug', 'tel_prefix', 'latitude', 'longitude', 'is_active', 'position'];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
