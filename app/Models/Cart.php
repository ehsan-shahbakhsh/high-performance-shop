<?php

namespace App\Models;

use App\Enums\CartStatus;
use App\Enums\CartType;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute as CastAttribute;

class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;
    use HasUlids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'shipping_address_id',
        'shipping_method_id',
        'type',
        'status',
        'meta',
        'locked_at',
        'lock_token',
        'last_activity_at',
        'completed_at',
        'abandoned_at',
        'items_count',
        'items_qty_sum',
        'subtotal',
        'discount_total',
        'shipping_total',
        'total',
        'version',
    ];

    protected $casts = [
        'type' => CartType::class,
        'status' => CartStatus::class,
        'meta' => 'json',
        'locked_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'completed_at' => 'datetime',
        'abandoned_at' => 'datetime',
        'items_count' => 'integer',
        'items_qty_sum' => 'integer',
        'subtotal' => 'integer',
        'discount_total' => 'integer',
        'shipping_total' => 'integer',
        'total' => 'integer',
        'version' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function isLocked(): bool
    {
        if (! $this->locked_at) {
            return false;
        }

        return $this->locked_at->gt(now()->subMinutes(15));
    }

    public function lock(string $token): void
    {
        $this->update([
            'locked_at' => now(),
            'lock_token' => $token,
        ]);
    }

    public function unlock(): void
    {
        $this->update([
            'locked_at' => null,
            'lock_token' => null,
        ]);
    }

    public function totalWeight(): CastAttribute
    {
        return new CastAttribute(
            get: function () {
                $defaultWeight = config('commerce.cart.default_product_weight', 500);

                return (int) ($this->items()
                    ->join('product_variants as v', 'v.id', '=', 'cart_items.product_variant_id')
                    ->selectRaw('SUM(COALESCE(v.weight, ?) * cart_items.quantity) AS total', [$defaultWeight])
                    ->value('total') ?? 0);
            },
        );
    }
}
