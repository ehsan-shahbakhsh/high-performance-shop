<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(!app()->isProduction());

        RateLimiter::for('otp', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip()),

                Limit::perMinutes(5, 3)->by($request->input('identifier') ?: $request->ip())
                    ->response(function (Request $request, array $headers) {
                        return response()->json([
                            'success' => false,
                            'code' => Response::HTTP_TOO_MANY_REQUESTS,
                            'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است.',
                            'data' => null,
                            'meta' => ['retry_after' => $headers['Retry-After'] ?? null],
                            'errors' => null,
                        ], Response::HTTP_TOO_MANY_REQUESTS);
                    }),
            ];
        });
    }
}
