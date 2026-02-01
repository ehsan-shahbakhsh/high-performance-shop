<?php

namespace App\Data\Auth;

use App\Models\User;
use Spatie\LaravelData\Data;

class AuthResultData extends Data
{
    public function __construct(
        public User $user,
        public mixed $authorization,
        public string $message,
        public int $status,
    ) {}
}
