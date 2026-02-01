<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use Database\Factories\InventoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Throwable;

class Inventory extends Model
{
    /** @use HasFactory<InventoryFactory> */
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'shelf_location',
        'low_stock_threshold',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    /**
     * @throws Throwable
     */
    public function adjustStock(int $change, InventoryTransactionType $type, ?string $reason = null, ?User $user = null): void
    {
        DB::transaction(function () use ($change, $type, $reason, $user) {
            $before = $this->quantity;
            $after = $before + $change;

            $this->transactions()->create([
                'user_id' => $user?->id,
                'type' => $type,
                'quantity' => $change,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reason' => $reason,
            ]);

            $this->update(['quantity' => $after]);

            $totalStock = Inventory::where('product_variant_id', $this->product_variant_id)
                ->sum(DB::raw('quantity - reserved_quantity'));

            $this->variant()->update(['stock_quantity' => $totalStock]);
        });
    }
}
