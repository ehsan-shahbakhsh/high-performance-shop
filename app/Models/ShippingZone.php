<?php

namespace App\Models;

use Database\Factories\ShippingZoneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    /** @use HasFactory<ShippingZoneFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['code', 'name', 'is_active', 'position'];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(ShippingZoneLocation::class, 'zone_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }
}
