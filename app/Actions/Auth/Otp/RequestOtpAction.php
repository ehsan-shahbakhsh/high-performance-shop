<?php

namespace App\Actions\Auth\Otp;

use App\Events\Auth\OtpRequested;
use Illuminate\Support\Facades\Cache;

class RequestOtpAction
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {}

    public function execute(string $identifier, string $type): int
    {
        $code = app()->isLocal() ? 1111 : rand(1000, 9999);

        $cacheKey = "otp_{$type}_{$identifier}";
        $attemptsKey = "otp_{$type}_{$identifier}_attempts";

        Cache::put($cacheKey, $code, now()->addMinutes(2));
        Cache::forget($attemptsKey);

        OtpRequested::dispatch($identifier, $code, $type);

        return 120;
    }
}
