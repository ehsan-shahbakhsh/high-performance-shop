<?php

namespace App\Models;

use Database\Factories\WishlistFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wishlist extends Model
{
    /** @use HasFactory<WishlistFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'is_default'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function isDefault(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return new \Illuminate\Database\Eloquent\Casts\Attribute(
            get: fn($value) => !is_null($value),
            set: fn($value) => $value ? 1 : null,
        );
    }
}
