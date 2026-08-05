<?php

namespace App\Models;

use Database\Factories\OrderItemDownloadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemDownload extends Model
{
    /** @use HasFactory<OrderItemDownloadFactory> */
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'user_id',
        'variant_file_id',
        'display_name',
        'download_limit',
        'download_count',
        'last_downloaded_at',
        'expires_at',
        'revoked_at',
        'token',
    ];

    protected $casts = [
        'download_limit' => 'integer',
        'download_count' => 'integer',
        'last_downloaded_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function variantFile(): BelongsTo
    {
        return $this->belongsTo(ProductVariantFile::class, 'variant_file_id');
    }
}
