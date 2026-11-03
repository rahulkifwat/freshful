<?php

// Freshful storefront v2 (new home page). Loaded by one guarded require at the end of routes/web.php,
// so it runs inside the web middleware group and after the old "/" route.

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Storefront\HomeV2Controller;
use App\Http\Controllers\Storefront\StockAlertController;
use Illuminate\Support\Facades\Route;

// Review address: always available, never replaces anything.
Route::get('/home-v2', [HomeV2Controller::class, 'index'])->name('home.v2');

// "Notify me when available" (logged-in customers).
Route::post('/ajax/stock-alert', [StockAlertController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('storefront.stock_alert');

// Switch: STOREFRONT_HOME_V2=true serves the new page at "/" (same route name "home"),
// and keeps the old page at /home-classic. Set it back to false to undo instantly.
if (config('storefront.home_v2')) {
    Route::get('/home-classic', [HomeController::class, 'index'])->name('home.classic');
    Route::get('/', [HomeV2Controller::class, 'index'])->name('home');
}
