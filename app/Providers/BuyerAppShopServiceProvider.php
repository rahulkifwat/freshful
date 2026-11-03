<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Customer app API, slice 2 (shopping extras), served under /api/app/*.
 * Self-contained, like BuyerAppServiceProvider: nothing in routes/api.php, the driver routes or slice 1 is touched.
 */
class BuyerAppShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        if (! $this->app->routesAreCached()) {
            Route::middleware('api')->prefix('api/app')->group(base_path('routes/buyer_app_shop.php'));
        }
    }
}
