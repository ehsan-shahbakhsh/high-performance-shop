<?php

namespace App\Models;

use Database\Factories\OrderNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNote extends Model
{
    /** @use HasFactory<OrderNoteFactory> */
    use HasFactory;

    protected $fillable = ['order_id', 'user_id', 'body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
