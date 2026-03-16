<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CartLockedException extends Exception
{
    public function __construct(
        string     $message = 'در حال حاضر امکان انجام این عملیات روی سبد خرید وجود ندارد زیرا فرآیند پرداخت آن در حال انجام است.',
        int        $code = Response::HTTP_FORBIDDEN,
        ?Throwable $previous = null,
    )
    {
        parent::__construct($message, $code, $previous);
    }

    public function render(Request $request): ?ApiResponse
    {
        if (!$request->is('api/*')) return null;

        return ApiResponse::error(
            $this->getMessage(),
            code: $this->getCode(),
        );
    }
}
