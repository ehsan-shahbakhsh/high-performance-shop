<?php

namespace App\Actions\Auth\Otp;

use App\Data\Auth\AuthorizationData;
use App\Data\Auth\AuthResultData;
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyOtpAction
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
    }

    /**
     * @throws Throwable
     */
    public function execute(string $identifier, string $code, string $type, ?string $userIp = null, ?string $userAgent = null): AuthResultData
    {
        $cacheKey = "otp_{$type}_{$identifier}";
        $attemptsKey = "otp_{$type}_{$identifier}_attempts";

        if (!Cache::has($cacheKey)) {
            throw new BusinessException(
                message: 'کد تأیید منقضی شده است. لطفاً درخواست ارسال مجدد دهید.',
                httpCode: Response::HTTP_GONE,
            );
        }

        $attempts = Cache::increment($attemptsKey);

        if ($attempts === 1) {
            Cache::put($attemptsKey, 1, now()->addMinutes(5));
        }

        if ($attempts >= 5) {
            Cache::forget($cacheKey);
            Cache::forget($attemptsKey);

            throw new BusinessException(
                message: 'تعداد تلاش‌های ناموفق بیش از حد مجاز بود. کد باطل شد.',
                httpCode: Response::HTTP_GONE,
            );
        }

        if (Cache::get($cacheKey) != $code) {
            throw new BusinessException(
                message: 'کد وارد شده اشتباه است. لطفاً مجدداً بررسی کنید.',
                httpCode: Response::HTTP_BAD_REQUEST,
            );
        }

        Cache::forget($cacheKey);
        Cache::forget($attemptsKey);

        return DB::transaction(function () use ($type, $identifier, $userIp, $userAgent) {
            $user = User::query()->firstOrCreate([$type => $identifier]);

            $isNewUser = $user->wasRecentlyCreated;

            if ($user->isBanned()) {
                throw new BusinessException(
                    message: 'حساب کاربری شما مسدود شده است. لطفاً با پشتیبانی تماس بگیرید.',
                    httpCode: Response::HTTP_FORBIDDEN,
                );
            }

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $userIp,
            ])->save();

            if ($type === 'mobile' && is_null($user->mobile_verified_at)) {
                $user->forceFill(['mobile_verified_at' => now()])->save();
            } elseif ($type === 'email' && !$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            $tokenName = $userAgent ?? 'Auth Token';
            $tokenName = Str::limit($tokenName, 50);

            $expirationMinutes = config('sanctum.expiration');

            $expirationTime = $expirationMinutes ? now()->addMinutes($expirationMinutes) : null;

            $newToken = $user->createToken($tokenName, ['*'], $expirationTime);

            $message = $isNewUser
                ? 'ثبت‌نام شما با موفقیت انجام شد.'
                : 'ورود با موفقیت انجام شد.';

            return new AuthResultData(
                user: $user,
                authorization: new AuthorizationData(
                    token: $newToken->plainTextToken,
                    type: 'Bearer',
                    expires_in_minutes: $expirationMinutes,
                    expires_at: $expirationTime?->toIso8601String(),
                ),
                message: $message,
                status: 200,
            );
        });
    }
}
