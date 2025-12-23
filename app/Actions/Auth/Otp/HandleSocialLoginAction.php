<?php

namespace App\Actions\Auth\Otp;

use App\Data\Auth\{AuthorizationData, AuthResultData, SocialUserData};
use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HandleSocialLoginAction
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
    public function execute(SocialUserData $userData): AuthResultData
    {
        return DB::transaction(function () use ($userData) {
            $user = User::query()->firstOrCreate(
                ['email' => $userData->email],
                [
                    'first_name' => $userData->firstName,
                    'last_name' => $userData->lastName,
                    'avatar' => $userData->avatar,
                ],
            );

            $isNewUser = $user->wasRecentlyCreated;

            if ($user->isBanned()) {
                throw new BusinessException(
                    message: 'حساب کاربری شما مسدود شده است. لطفاً با پشتیبانی تماس بگیرید.',
                    httpCode: Response::HTTP_FORBIDDEN,
                );
            }

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $userData->userIp,
            ])->save();

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            $user->socialAccounts()->updateOrCreate(
                [
                    'provider' => $userData->provider->value,
                    'provider_id' => $userData->providerId,
                ],
                [
                    'token' => $userData->token,
                    'avatar' => $userData->avatar,
                ]
            );

            $expirationMinutes = config('sanctum.expiration');

            $expirationTime = $expirationMinutes ? now()->addMinutes($expirationMinutes) : null;

            $tokenName = "{$userData->provider->value}-login";
            $newToken = $user->createToken($tokenName, ['*'], $expirationTime);

            $message = $isNewUser
                ? 'حساب کاربری شما با گوگل با موفقیت ساخته شد.'
                : 'ورود با حساب گوگل با موفقیت انجام شد.';

            return new AuthResultData(
                user: $user,
                authorization: new AuthorizationData(
                    token: $newToken->plainTextToken,
                    type: 'Bearer',
                    expires_in_minutes: $expirationMinutes,
                    expires_at: $expirationTime,
                ),
                message: $message,
                status: 200,
            );
        });
    }
}
