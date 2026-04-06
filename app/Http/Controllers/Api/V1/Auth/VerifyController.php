<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\Otp\VerifyOtpAction;
use App\Events\Auth\UserAuthenticated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\VerifyRequest;
use App\Http\Resources\V1\Auth\AuthUserResource;
use App\Http\Responses\ApiResponse;
use Throwable;

class VerifyController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(VerifyRequest $request, VerifyOtpAction $action)
    {
        $inputs = $request->validated();

        $identifier = $inputs['identifier'];
        $type = $request->getFieldType();

        $result = $action->execute(
            $identifier,
            $inputs['code'],
            $type,
            $request->ip(),
            $request->userAgent(),
        );

        event(new UserAuthenticated($result->user, $request->header('Session-Id')));

        return ApiResponse::success([
            'user' => new AuthUserResource($result->user),
            'authorization' => $result->authorization,
        ], $result->message);
    }
}
