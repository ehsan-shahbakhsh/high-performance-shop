<?php

use App\Http\Controllers\Api\V1\Auth\{
    GoogleController,
    LogoutController,
    MeController,
    OtpController,
    VerifyController,
};
use App\Http\Controllers\Api\V1\Catalog\ProductCategoryController;
use App\Http\Controllers\Api\V1\Sales\Cart\{CartController, CartItemController};
use App\Http\Controllers\Api\V1\Sales\Wishlist\{WishlistController, WishlistItemController};
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('guest')->prefix('auth')->group(function () {
        Route::middleware('throttle:login_google')->group(function () {
            Route::get('google/redirect', [GoogleController::class, 'redirect']);
            Route::get('google/callback', [GoogleController::class, 'callback']);
        });

        Route::middleware('throttle:otp')->post('otp/request', OtpController::class);
        Route::post('otp/verify', VerifyController::class);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('me', MeController::class);
            Route::post('logout', LogoutController::class);
        });

        Route::prefix('shop')->group(function () {
            Route::controller(ProductCategoryController::class)->group(function () {
                Route::get('menus/main', 'index');
            });
        });

        Route::prefix('carts/{cart}/items')
            ->controller(CartItemController::class)
            ->scopeBindings()
            ->group(function () {
                Route::post('{item}/move', 'move');
            });

        Route::apiResource('wishlists', WishlistController::class)->except('show');

        Route::prefix('wishlists/{wishlist}/items')
            ->scopeBindings()
            ->controller(WishlistItemController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::delete('{item}', 'destroy');
            });
    });

    Route::middleware('auth.optional')->group(function () {
        Route::get('cart', CartController::class);

        Route::prefix('cart-items')->controller(CartItemController::class)->group(function () {
            Route::post('/', 'store');
            Route::match(['put', 'patch'], '{item}', 'update');
            Route::delete('{item}', 'destroy');
        });
    });
});
