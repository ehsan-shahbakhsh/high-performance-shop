<?php

namespace App\Models;

use App\Enums\Sales\PaymentMethodDriver;
use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\{HasMedia, InteractsWithMedia, MediaCollections\Models\Media};
use Spatie\Image\Enums\Fit;

class PaymentMethod extends Model implements HasMedia
{
    use InteractsWithMedia;
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['name', 'driver', 'description', 'settings', 'is_active', 'position'];

    protected $casts = [
        'driver' => PaymentMethodDriver::class,
        'settings' => 'json',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('payment_method_logo')
            ->singleFile()
            ->useDisk('media')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        if (!$media || !str_starts_with($media->mime_type, 'image/')) {
            return;
        }

        $this->addMediaConversion('icon')
            ->fit(Fit::Crop, 100, 100)
            ->format('webp')
            ->nonQueued()
            ->performOnCollections('payment_method_logo');
    }
}
