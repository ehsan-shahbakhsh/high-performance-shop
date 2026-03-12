<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

final class ApiResponse implements Responsable
{
    public function __construct(
        private readonly mixed  $data = null,
        private ?string         $message = null,
        private readonly bool   $success = true,
        private readonly ?array $errors = null,
        private readonly array  $meta = [],
        private readonly int    $code = ResponseCode::HTTP_OK,
        private readonly int    $httpStatus = ResponseCode::HTTP_OK,
        private readonly array  $headers = [],
    )
    {
        $this->message = $message ?? ($success ? 'Success' : 'Error');
    }

    public
    function toResponse($request): JsonResponse
    {
        $payload = [
            'success' => $this->success,
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
            'meta' => !empty($this->meta) ? $this->meta : null,
            'errors' => $this->errors,
        ];

        if ($this->data instanceof ResourceCollection && $this->data->resource instanceof AbstractPaginator) {
            $paginated = $this->data->resource->toArray();

            $payload['data'] = $this->data->resolve();

            $payload['meta'] = array_merge($this->meta, [
                'current_page' => $paginated['current_page'] ?? null,
                'last_page' => $paginated['last_page'] ?? null,
                'per_page' => $paginated['per_page'] ?? null,
                'total' => $paginated['total'] ?? null,
            ]);
        }

        return response()->json(
            data: $payload,
            status: $this->httpStatus,
            headers: $this->headers,
        );
    }

    public static function success(mixed $data = null, ?string $message = null, int $code = ResponseCode::HTTP_OK, array $meta = []): self
    {
        return new self(
            data: $data,
            message: $message,
            success: true,
            meta: $meta,
            code: $code,
            httpStatus: $code,
        );
    }

    public static function created(mixed $data = null, ?string $message = 'Resource created successfully'): self
    {
        return new self(
            data: $data,
            message: $message,
            success: true,
            code: ResponseCode::HTTP_CREATED,
            httpStatus: ResponseCode::HTTP_CREATED,
        );
    }

    public static function deleted(string $message = 'Resource deleted successfully'): self
    {
        return self::success(message: $message);
    }

    public static function error(string $message, int $code = ResponseCode::HTTP_BAD_REQUEST, ?array $errors = null, int $httpStatus = null): self
    {
        return new self(
            message: $message,
            success: false,
            errors: $errors,
            code: $code,
            httpStatus: $httpStatus ?? $code,
        );
    }

    public static function badRequest(string $message = 'Bad Request', ?array $errors = null): self
    {
        return self::error(
            message: $message,
            code: ResponseCode::HTTP_BAD_REQUEST,
            errors: $errors,
        );
    }

    public static function unauthorized(string $message = 'Unauthenticated'): self
    {
        return self::error(
            message: $message,
            code: ResponseCode::HTTP_UNAUTHORIZED,
        );
    }

    public static function notFound(string $message = 'Resource not found'): self
    {
        return self::error($message, ResponseCode::HTTP_NOT_FOUND);
    }

    public static function forbidden(string $message = 'Access denied'): self
    {
        return self::error($message, ResponseCode::HTTP_FORBIDDEN);
    }

    public static function validationFailed(string $message = 'Validation errors', array $errors = []): self
    {
        return self::error(
            message: $message,
            code: ResponseCode::HTTP_UNPROCESSABLE_ENTITY,
            errors: $errors,
        );
    }

    public static function tooManyRequests(string $message = 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً کمی بعد دوباره تلاش کنید.', array $meta = []): self
    {
        return new self(
            message: $message,
            success: false,
            meta: $meta,
            code: ResponseCode::HTTP_TOO_MANY_REQUESTS,
            httpStatus: ResponseCode::HTTP_TOO_MANY_REQUESTS,
        );
    }

    public static function internalServerError(string $message = 'Internal Server Error', ?array $errors = null): self
    {
        return self::error(
            message: $message,
            code: ResponseCode::HTTP_INTERNAL_SERVER_ERROR,
            errors: $errors,
        );
    }
}