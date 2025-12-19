<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Events\OtpRequested;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\OtpRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Cache;

class OtpController extends Controller
{
    public function __invoke(OtpRequest $request)
    {
        $identifier = $request->validated('identifier');
        $type = $request->getFieldType();

        $code = app()->isLocal() ? 1111 : rand(1000, 9999);

        $cacheKey = "otp_{$type}_{$identifier}";
        Cache::put($cacheKey, $code, now()->addMinutes(2));

        OtpRequested::dispatch($identifier, $type, $code);

        return ApiResponse::success(
            message: "کد تایید برای {$identifier} ارسال شد.",
            meta: ['ttl' => 120],
        );
    }
}
