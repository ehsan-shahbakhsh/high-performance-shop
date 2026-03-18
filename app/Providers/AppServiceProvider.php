<?php

namespace App\Providers;

use Illuminate\Support\Facades\Date;
use App\Models\{Warehouse, User};
use App\Observers\{WarehouseObserver, UserObserver};
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
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
                    ->by($request->input('identifier') ?: $request->ip()),
            ];
        });

        RateLimiter::for('login_google', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        Warehouse::observe(WarehouseObserver::class);
        User::observe(UserObserver::class);

        Date::serializeUsing(function ($date) {
            return $date->toIso8601String();
        });
    }
}
