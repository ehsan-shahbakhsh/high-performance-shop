<?php

namespace App\Models;

use App\Data\Sales\TransactionGatewayPayloadData;
use App\Enums\Sales\TransactionStatus;
use App\Enums\Sales\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'user_id',
        'payment_method_id',
        'type',
        'amount',
        'status',
        'token',
        'reference_id',
        'gateway_payload',
        'paid_at',
        'refunded_at',
        'description',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'amount' => 'integer',
        'status' => TransactionStatus::class,
        'gateway_payload' => TransactionGatewayPayloadData::class,
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
