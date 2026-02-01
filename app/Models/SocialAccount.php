<?php

namespace App\Models;

use App\Enums\SocialAccountProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\SocialAccountFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    /** @use HasFactory<SocialAccountFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'provider', 'provider_id', 'token', 'avatar'];

    protected $casts = [
        'provider' => SocialAccountProvider::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
