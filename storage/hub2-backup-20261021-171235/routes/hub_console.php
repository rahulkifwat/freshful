<?php

/*
| Freshful hub console (/hub/console). Added 22 Oct 2026.
| The hub manager's command centre: overview + smart insights, prep list, inventory, orders.
| /hub/dashboard (old page) now opens the console; every other old /hub page is unchanged.
| Loaded by ONE guarded require line at the end of routes/web.php.
*/

use App\Http\Controllers\HubConsole\HubConsoleController;
use App\Http\Middleware\HubConsoleAuth;
use Illuminate\Support\Facades\Route;

Route::middleware([HubConsoleAuth::class, 'throttle:240,1'])->prefix('hub/console')->group(function () {
    Route::get('/', [HubConsoleController::class, 'app'])->name('hubc.home');
    Route::get('/api/overview', [HubConsoleController::class, 'overview'])->name('hubc.overview');
    Route::get('/api/prep', [HubConsoleController::class, 'prep'])->name('hubc.prep');
    Route::get('/api/inventory', [HubConsoleController::class, 'inventory'])->name('hubc.inventory');
    Route::get('/api/orders', [HubConsoleController::class, 'orders'])->name('hubc.orders');
    Route::post('/api/inward', [HubConsoleController::class, 'inward'])->middleware('throttle:60,1')->name('hubc.inward');
    Route::post('/api/count', [HubConsoleController::class, 'count'])->middleware('throttle:120,1')->name('hubc.count');
});

// The old dashboard address opens the new console (same name, so every existing link keeps working).
Route::middleware([HubConsoleAuth::class])->get('hub/dashboard', fn () => redirect()->route('hubc.home'))->name('hub.dashboard');
