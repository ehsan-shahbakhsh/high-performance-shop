<?php

namespace App\Models;

use Database\Factories\ProductVariantFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\{HasMedia, InteractsWithMedia};

class ProductVariantFile extends Model implements HasMedia
{
    use InteractsWithMedia;
    /** @use HasFactory<ProductVariantFileFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'display_name',
        'version',
        'download_limit',
        'expiry_days',
        'position',
        'is_active',
    ];

    protected $casts = [
        'download_limit' => 'integer',
        'expiry_days' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('variant_file')
            ->singleFile()
            ->useDisk('local')
            ->acceptsMimeTypes([
                'application/zip',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
                'audio/mpeg',
                'video/mp4',
            ]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
