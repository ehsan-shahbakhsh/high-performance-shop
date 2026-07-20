<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class LogoutController extends Controller
{
    #[OA\Post(
        path: "/v1/auth/logout",
        operationId: "logoutAccount",
        description: "این اندپوینت توکن فعلی کاربر را باطل (Revoke) کرده و دسترسی او را از این دستگاه مسدود می‌کند.",
        summary: "خروج کاربر از سیستم (ابطال توکن فعلی)",
        security: [["sanctum" => []]],
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'کاربر با موفقیت از حساب خارج شد.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code', type: 'integer', example: Response::HTTP_OK),
                        new OA\Property(property: 'message', type: 'string', example: 'با موفقیت از حساب خارج شدید.', nullable: true),
                        new OA\Property(property: 'data', default: null),
                        new OA\Property(property: 'meta', default: null),
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                        new OA\Property(property: 'error_code', type: 'string', nullable: true),
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'خطا در خروج از حساب',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'code', type: 'integer', example: Response::HTTP_UNAUTHORIZED),
                        new OA\Property(property: 'message', type: 'string', example: 'لطفاً ابتدا وارد حساب کاربری شوید.', nullable: true),
                        new OA\Property(property: 'data', default: null),
                        new OA\Property(property: 'meta', default: null),
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                        new OA\Property(property: 'error_code', type: 'string', nullable: true),
                    ]
                )
            ),
        ],
    )]
    public function __invoke(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(message: 'با موفقیت از حساب خارج شدید.');
    }
}
