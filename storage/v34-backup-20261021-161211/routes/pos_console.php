<?php

/*
| Freshful POS console (/pos/console). Added 21 Oct 2026, v3 the same day.
| Live order board for hub counters. The old /pos pages are not touched.
| Loaded by ONE guarded require line at the end of routes/web.php.
*/

use App\Http\Controllers\PosConsole\PosConsoleAuthController;
use App\Http\Controllers\PosConsole\PosConsoleController;
use App\Http\Middleware\PosConsoleAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('pos/console')->group(function () {
    Route::get('/login', [PosConsoleAuthController::class, 'showLogin'])->name('posc.login');
    Route::post('/login', [PosConsoleAuthController::class, 'login'])->middleware('throttle:30,1')->name('posc.login.submit');
    Route::get('/logout', [PosConsoleAuthController::class, 'logout'])->name('posc.logout');

    Route::middleware([PosConsoleAuth::class, 'throttle:240,1'])->group(function () {
        $id = '[A-Za-z0-9_\-]{1,40}';
        Route::get('/', [PosConsoleController::class, 'board'])->name('posc.board');
        Route::get('/feed', [PosConsoleController::class, 'feed'])->name('posc.feed');
        Route::get('/search', [PosConsoleController::class, 'search'])->name('posc.search');
        Route::get('/history', [PosConsoleController::class, 'history'])->name('posc.history');
        Route::get('/hubs', [PosConsoleController::class, 'hubs'])->name('posc.hubs');
        Route::get('/report', [PosConsoleController::class, 'report'])->name('posc.report');
        Route::post('/report/close', [PosConsoleController::class, 'closeDay'])->middleware('throttle:10,1')->name('posc.report.close');
        Route::post('/settle', [PosConsoleController::class, 'settle'])->middleware('throttle:60,1')->name('posc.settle');
        Route::post('/stale/close', [PosConsoleController::class, 'closeStale'])->middleware('throttle:6,1')->name('posc.stale.close');
        Route::get('/order/{orderId}', [PosConsoleController::class, 'order'])->where('orderId', $id)->name('posc.order');
        Route::post('/order/{orderId}/action', [PosConsoleController::class, 'action'])->where('orderId', $id)->name('posc.action');
        Route::post('/order/{orderId}/weigh', [PosConsoleController::class, 'weigh'])->where('orderId', $id)->name('posc.weigh');
        Route::post('/order/{orderId}/relocate', [PosConsoleController::class, 'relocate'])->where('orderId', $id)->middleware('throttle:30,1')->name('posc.relocate');
        Route::get('/order/{orderId}/print/{format}', [PosConsoleController::class, 'print'])
            ->where(['orderId' => $id, 'format' => 'thermal|a4|pick'])->name('posc.print');
    });
});
