<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Customer app API, commerce part (service area, payment methods, later coupons and payments), under /api/app/*.
 * Self-contained, like BuyerAppServiceProvider: nothing existing is edited.
 */
class BuyerAppCommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        if (! $this->app->routesAreCached()) {
            Route::middleware('api')->prefix('api/app')->group(base_path('routes/buyer_app_commerce.php'));
        }
    }
}
