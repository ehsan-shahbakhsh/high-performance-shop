<?php

namespace App\Models;

use App\Traits\RestoreOrFail;
use Database\Factories\ShippingCarrierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingCarrier extends Model
{
    /** @use HasFactory<ShippingCarrierFactory> */
    use HasFactory;
    use SoftDeletes;
    use RestoreOrFail;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'is_active',
        'tracking_url_template',
        'logo_path',
        'settings',
        'position',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'json',
        'position' => 'integer',
    ];

    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class, 'carrier_id');
    }
}
