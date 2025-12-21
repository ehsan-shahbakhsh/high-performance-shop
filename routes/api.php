<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\V1\Auth\{
    GoogleController,
    OtpController,
    VerifyController,
    LogoutController,
    MeController,
};

Route::prefix('v1')->group(function () {
    Route::middleware('guest')->prefix('auth')->group(function () {
        Route::get('google/redirect', [GoogleController::class, 'redirect']);
        Route::get('google/callback', [GoogleController::class, 'callback']);

        Route::middleware('throttle:otp')->post('otp/request', OtpController::class);
        Route::post('otp/verify', VerifyController::class);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('me', MeController::class);
            Route::post('logout', LogoutController::class);
        });
    });
});
