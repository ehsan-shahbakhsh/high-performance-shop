<?php

namespace App\Providers;

use App\Models\Warehouse;
use App\Observers\WarehouseObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use App\Sms\Interfaces\SmsDriverInterface;
use App\Sms\Drivers\LogDriver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsDriverInterface::class, function () {
            return new LogDriver;
        });
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

                Limit::perMinutes(5, 3)
                    ->by($request->input('identifier') ?: $request->ip())
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

        RateLimiter::for('login_google', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'code' => Response::HTTP_TOO_MANY_REQUESTS,
                        'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است.',
                        'data' => null,
                        'meta' => ['retry_after' => $headers['Retry-After'] ?? null],
                        'errors' => null,
                    ], Response::HTTP_TOO_MANY_REQUESTS);
                });
        });

        Warehouse::observe(WarehouseObserver::class);
    }
}
