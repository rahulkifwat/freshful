<?php

/*
| Freshful Control Centre update 1 (15 Nov 2026).
| Loaded by one guarded require inside the /admin/console/api/ad group of routes/admin_console.php,
| so every route here needs the admin login. URL /admin/console/api/ad/cc/*, route names adc.ad.cc.*
*/

use App\Http\Controllers\ControlCentre\ControlCentreController as K;
use Illuminate\Support\Facades\Route;

Route::prefix('cc')->group(function () {
    Route::get('/metrics', [K::class, 'metrics'])->name('adc.ad.cc.metrics');
    Route::get('/health', [K::class, 'health'])->name('adc.ad.cc.health');
    Route::get('/alerts', [K::class, 'alerts'])->name('adc.ad.cc.alerts');
    Route::post('/alerts/scan', [K::class, 'scan'])->middleware('throttle:10,1')->name('adc.ad.cc.scan');
    Route::get('/alerts/{id}/history', [K::class, 'history'])->whereNumber('id')->name('adc.ad.cc.history');
    Route::get('/ai/status', [K::class, 'aiStatus'])->name('adc.ad.cc.ai.status');
    Route::get('/ai/history', [K::class, 'aiHistory'])->name('adc.ad.cc.ai.history');
    Route::post('/ai/ask', [K::class, 'aiAsk'])->middleware('throttle:20,1')->name('adc.ad.cc.ai.ask');
    Route::post('/alerts/{id}/{action}', [K::class, 'act'])->whereNumber('id')->where('action', 'acknowledge|assign|dismiss|reopen')->middleware('throttle:60,1')->name('adc.ad.cc.act');
});
