<?php

namespace App\Providers;

use Filament\Actions\Action;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\{Context, Date, RateLimiter, DB};
use App\Models\{ProductVariant, Warehouse, User};
use App\Observers\{ProductVariantObserver, WarehouseObserver, UserObserver};
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
        ProductVariant::observe(ProductVariantObserver::class);

        Date::serializeUsing(function ($date) {
            return $date->toIso8601String();
        });

        Action::macro('handleWith', function (
            callable $callback,
            bool     $useTransaction = true,
        ) {
            /** @var Action $this */

            return $this->action(static function (Model $record, Action $action) use ($callback, $useTransaction) {
                $executor = static fn() => $callback($record, $action);

                $result = rescue(
                    static fn() => $useTransaction
                        ? DB::transaction($executor)
                        : $executor(),
                    false,
                    false,
                );

                if (!$result) {
                    $action->failure();
                    return;
                }

                $action->success();
            });
        });

        \Event::listen(function (Authenticated $event) {
            Context::add('user-id', $event->user->id);
        });
    }
}
