<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Auth\AuthUserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class MeController extends Controller
{
    /**
     * Get the authenticated user.
     */
    #[OA\Get(
        path: "/v1/auth/me",
        operationId: "getMeProfile",
        description: "این اندپوینت اطلاعات کاربری که توکن معتبر دارد را برمی‌گرداند.",
        summary: "دریافت اطلاعات پروفایل کاربر فعلی",
        security: [["sanctum" => []]],
        tags: ["Profile"],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'اطلاعات کاربر با موفقیت دریافت شد.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'code', type: 'integer', example: Response::HTTP_OK),
                        new OA\Property(property: 'message', type: 'string', example: 'اطلاعات با موفقیت یافت شد.', nullable: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/AuthUserResource'),
                        new OA\Property(property: 'meta', properties: [
                            new OA\Property(property: 'current_page', type: 'integer', example: 1),
                            new OA\Property(property: 'last_page', type: 'integer', example: 10),
                            new OA\Property(property: 'per_page', type: 'integer', example: 20),
                            new OA\Property(property: 'total', type: 'integer', example: 200),
                        ], type: 'object', nullable: true),
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                        new OA\Property(property: 'error_code', type: 'string', nullable: true),
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: 'خطا در دریافت اطلاعات کاربر',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'code', type: 'integer', example: Response::HTTP_UNAUTHORIZED),
                        new OA\Property(property: 'message', type: 'string', example: 'لطفاً ابتدا وارد حساب کاربری شوید.', nullable: true),
                        new OA\Property(property: 'data', default: null),
                        new OA\Property(property: 'meta', type: 'object', default: null, nullable: true),
                        new OA\Property(property: 'errors', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                        new OA\Property(property: 'error_code', type: 'string', nullable: true),
                    ]
                )
            ),
        ],
    )]
    public function __invoke(Request $request)
    {
        return ApiResponse::success(new AuthUserResource($request->user()));
    }
}
