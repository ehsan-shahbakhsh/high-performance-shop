<?php

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'recipient_first_name', 'recipient_last_name', 'recipient_mobile',
        'province_id', 'city_id', 'title', 'address_line', 'plaque', 'unit', 'postal_code',
        'latitude', 'longitude', 'is_default',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function toSnapshotArray(): array
    {
        return [
            'recipient' => [
                'first_name' => $this->recipient_first_name,
                'last_name' => $this->recipient_last_name,
                'mobile' => $this->recipient_mobile,
            ],

            'location' => [
                'province_id' => $this->province_id,
                'province_name' => $this->province?->name,
                'city_id' => $this->city_id,
                'city_name' => $this->city?->name,
            ],

            'address' => [
                'title' => $this->title,
                'address_line' => $this->address_line,
                'plaque' => $this->plaque,
                'unit' => $this->unit,
                'postal_code' => $this->postal_code,
            ],

            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
        ];
    }
}
