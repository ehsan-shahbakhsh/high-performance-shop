<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

use App\Exceptions\BusinessException;
use Symfony\Component\HttpKernel\Exception\{
    NotFoundHttpException,
    AccessDeniedHttpException,
    MethodNotAllowedHttpException,
};
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Responses\ApiResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([
            BusinessException::class,
        ]);

        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e): bool {
            return $request->is('api/*') || $request->wantsJson();
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $message = $e->getPrevious() instanceof ModelNotFoundException
                ? 'رکورد مورد نظر یافت نشد.'
                : 'آدرس مورد نظر یافت نشد.';

            if ($request->is('api/*')) {
                return ApiResponse::notFound($message);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::forbidden('شما دسترسی لازم برای انجام این عملیات را ندارید.');
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::unauthorized('لطفاً ابتدا وارد حساب کاربری شوید.');
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::validationFailed('اطلاعات ورودی معتبر نیست.', $e->errors());
            }
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return new ApiResponse(
                    message: 'متد ارسالی برای این آدرس صحیح نیست.',
                    success: false,
                    httpStatus: Response::HTTP_METHOD_NOT_ALLOWED,
                );
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof ThrottleRequestsException || $e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                return false;
            }

            if ($request->is('api/*')) {
                $isDebug = config('app.debug');

                return ApiResponse::internalServerError(
                    message: $isDebug ? $e->getMessage() : 'خطای داخلی سرور رخ داده است.',
                    errors: $isDebug ? [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => collect($e->getTrace())->take(5)->all(),
                    ] : null,
                );
            }
        });
    })->create();
