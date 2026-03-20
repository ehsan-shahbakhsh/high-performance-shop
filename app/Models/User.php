<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use App\Enums\{CartType, CartStatus};

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name', 'last_name', 'mobile', 'email',
        'mobile_verified_at', 'email_verified_at',
        'password', 'avatar', 'is_admin',
        'banned_at', 'last_login_at', 'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'banned_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function isBanned(): bool
    {
        return !is_null($this->banned_at);
    }

    public function isVerified(): bool
    {
        return $this->mobile_verified_at || $this->email_verified_at;
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function mainCart(): HasOne
    {
        return $this->hasOne(Cart::class)
            ->where('type', CartType::Main)
            ->where('status', CartStatus::Active);
    }

    public function secondaryCart(): HasOne
    {
        return $this->hasOne(Cart::class)
            ->where('type', CartType::Secondary)
            ->where('status', CartStatus::Active);
    }

    public function defaultWishlist(): HasOne
    {
        return $this->hasOne(Wishlist::class)
                ->where('is_default', true);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function fullName(): Attribute
    {
        return new Attribute(
            get: fn() => collect([$this->first_name, $this->last_name])->filter()->implode(' '),
        );
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }
}
