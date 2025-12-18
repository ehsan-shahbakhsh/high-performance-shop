<?php

namespace App\Models;

use App\Enums\WalletTransactionTypeEnum;
use Database\Factories\WalletTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    /** @use HasFactory<WalletTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'wallet_id', 'uuid', 'type', 'amount',
        'balance_before', 'balance_after', 'confirmed',
        'related_type', 'related_id', 'meta',
    ];

    protected $casts = [
        'type' => WalletTransactionTypeEnum::class,
        'confirmed' => 'boolean',
        'meta' => 'json',// todo: change to laravel data class
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo('related');
    }
}
