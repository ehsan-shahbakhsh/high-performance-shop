<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

class AuthorizationData extends Data
{
    public function __construct(
        public string $token,
        public string $type,
        public int $expires_in_minutes,
        public $expires_at,
    ) {}
}
