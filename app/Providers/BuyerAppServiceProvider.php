<?php

namespace App\Providers;

use App\Services\BuyerApp\DbOtpStore;
use App\Services\BuyerApp\GatewaySmsSender;
use App\Services\BuyerApp\OtpService;
use App\Services\BuyerApp\OtpStore;
use App\Services\BuyerApp\SmsSender;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Customer app API (Expo app), served under /api/app/*.
 * Self-contained: nothing in routes/api.php (driver and legacy routes) is touched.
 */
class BuyerAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OtpStore::class, DbOtpStore::class);
        $this->app->bind(SmsSender::class, GatewaySmsSender::class);
        $this->app->singleton(OtpService::class, function ($app) {
            return new OtpService($app->make(OtpStore::class), $app->make(SmsSender::class), (string) config('app.key'));
        });
    }

    public function boot(): void
    {
        if (! $this->app->routesAreCached()) {
            Route::middleware('api')->prefix('api/app')->group(base_path('routes/buyer_app.php'));
        }
    }
}
