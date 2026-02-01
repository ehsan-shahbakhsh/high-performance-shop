<?php

namespace App\Models;

use Database\Factories\ProductFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class ProductFile extends Model
{
    /** @use HasFactory<ProductFileFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'display_name',
        'filename',
        'disk',
        'storage_path',
        'size',
        'mime_type',
        'download_limit',
        'expiry_days',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'size' => 'integer',
        'download_limit' => 'integer',
        'expiry_days' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getSizeFormattedAttribute(): string
    {
        return Number::fileSize($this->size);
    }

    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->storage_path);
    }

    public function getSecureDownloadUrl(int $expirationMinutes = 60): string
    {
        return Storage::disk($this->disk)->temporaryUrl(
            $this->storage_path,
            now()->addMinutes($expirationMinutes)
        );
    }
}
