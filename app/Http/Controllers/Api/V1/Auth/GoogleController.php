<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\Otp\HandleSocialLoginAction;
use App\Data\Auth\SocialUserData;
use App\Enums\SocialAccountProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Auth\AuthUserResource;
use App\Http\Responses\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Laravel\Socialite\Socialite;
use Throwable;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver(SocialAccountProvider::Google->value)->stateless()->redirect();
    }

    /**
     * @throws Throwable
     */
    public function callback(Request $request, HandleSocialLoginAction $action)
    {
        try {
            $socialiteUser = Socialite::driver(SocialAccountProvider::Google->value)->stateless()->user();

            $data = SocialUserData::fromSocialite(
                $socialiteUser,
                SocialAccountProvider::Google,
                $request->ip(),
            );

            $result = $action->execute($data);

            return ApiResponse::success([
                'user' => new AuthUserResource($result->user),
                'authorization' => $result->authorization,
            ], $result->message);
        } catch (Exception $e) {
            report($e);

            return ApiResponse::error('فرآیند ورود با گوگل کامل نشد. لطفاً دوباره تلاش کنید.');
        }
    }
}
