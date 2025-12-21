<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\Otp\RequestOtpAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\OtpRequest;
use App\Http\Responses\ApiResponse;

class OtpController extends Controller
{
    public function __invoke(OtpRequest $request, RequestOtpAction $action)
    {
        $identifier = $request->validated('identifier');
        $type = $request->getFieldType();

        $result = $action->execute($identifier, $type);

        return ApiResponse::success(
            message: "کد تایید برای {$identifier} ارسال شد.",
            meta: ['ttl' => $result],
        );
    }
}
