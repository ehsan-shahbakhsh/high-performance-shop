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
    HttpException,
};
use Illuminate\Http\Exceptions\{
    ThrottleRequestsException,
    PostTooLargeException,
};
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Responses\ApiResponse;
use App\Http\Middleware\OptionalSanctumAuth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.optional' => OptionalSanctumAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReportDuplicates();

        $exceptions->dontReport([
            BusinessException::class,
        ]);

        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e): bool {
            return $request->is('api/*') || $request->wantsJson();
        });

        $exceptions->render(function (BusinessException $e, Request $request) {
            if (!$request->is('api/*')) return null;

            return ApiResponse::error(
                $e->getMessage(),
                code: $e->getCode() ?: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (!$request->is('api/*')) return null;

            $message = $e->getPrevious() instanceof ModelNotFoundException
                ? 'موردی با این مشخصات یافت نشد.'
                : 'آدرس مورد نظر یافت نشد.';

            return ApiResponse::notFound($message);
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (!$request->is('api/*')) return null;

            return ApiResponse::notFound('موردی با این مشخصات یافت نشد.');
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (!$request->is('api/*')) return null;

            return ApiResponse::forbidden('شما دسترسی لازم برای انجام این عملیات را ندارید.');
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (!$request->is('api/*')) return null;

            return ApiResponse::unauthorized('لطفاً ابتدا وارد حساب کاربری شوید.');
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (!$request->is('api/*')) return null;

            return ApiResponse::validationFailed('اطلاعات ورودی معتبر نیست.', $e->errors());
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if (!$request->is('api/*')) return null;

            return ApiResponse::error(
                'متد ارسالی برای این آدرس صحیح نیست.',
                code: Response::HTTP_METHOD_NOT_ALLOWED,
            );
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (!$request->is('api/*')) return null;

            return ApiResponse::tooManyRequests(meta: ['retry_after' => $e->getHeaders()['Retry-After'] ?? null]);
        });

        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if (!$request->is('api/*')) return null;

            return ApiResponse::error(
                'حجم فایل ارسالی بیش از حد مجاز است.',
                code: Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
            );
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            if (!$request->is('api/*')) return null;

            $sqlState = $e->errorInfo[0] ?? null;

            // duplicate key
            if ($sqlState === '23000') {
                return ApiResponse::error(
                    'اطلاعات وارد شده قبلاً ثبت شده است.',
                    code: Response::HTTP_CONFLICT,
                );
            }

            return ApiResponse::internalServerError('خطایی در پردازش اطلاعات رخ داده است.');
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if (!$request->is('api/*')) return null;

            $status = $e->getStatusCode();

            return ApiResponse::error(
                $e->getMessage() ?: 'خطایی در پردازش درخواست رخ داده است.',
                code: $status,
            );
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
                return false;
            }

            if (!$request->is('api/*')) return null;

            $isDebug = config('app.debug');

            return ApiResponse::internalServerError(
                message: $isDebug ? $e->getMessage() : 'خطای غیرمنتظره‌ای رخ داده است. لطفاً کمی بعد مجدداً تلاش کنید.',
                errors: $isDebug ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->take(5)->all(),
                ] : null,
            );
        });
    })->create();
