<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Exception;
use Throwable;

class BusinessException extends Exception
{
    /**
     * Create a new business exception instance.
     *
     * @param string $message The user-friendly error message.
     * @param int $httpCode The HTTP status code (default: 400 Bad Request).
     * @param int $code Internal error code (optional).
     * @param Throwable|null $previous The previous exception used for chaining (optional).
     */
    public function __construct(
        string            $message,
        protected int     $httpCode = HttpResponse::HTTP_BAD_REQUEST,
        protected ?string $errorCode = null,
        int               $code = 0,
        ?Throwable        $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception into an HTTP response.
     *
     * Laravel automatically calls this method when the exception is thrown.
     *
     * @param Request $request
     * @return ?ApiResponse
     */
    public function render(Request $request): ?ApiResponse
    {
        if (!$request->is('api/*')) return null;

        return ApiResponse::error(
            $this->getMessage(),
            code: $this->httpCode,
            errorCode: $this->errorCode,
        );
    }
}
