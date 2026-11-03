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
        Route::post('/rider/{riderId}/status', [PosConsoleController::class, 'riderStatus'])->whereNumber('riderId')->middleware('throttle:60,1')->name('posc.rider.status');
        Route::post('/order/{orderId}/rider', [PosConsoleController::class, 'reassign'])->where('orderId', $id)->name('posc.reassign');
        Route::post('/order/{orderId}/assign', [\App\Http\Controllers\PosConsole\PosRiderFlowController::class, 'assign'])->where('orderId', $id)->name('posc.assign');
        Route::get('/assignments', [\App\Http\Controllers\PosConsole\PosRiderFlowController::class, 'assignments'])->name('posc.assignments');
        Route::post('/rider/{riderId}/handover', [\App\Http\Controllers\PosConsole\PosRiderFlowController::class, 'handover'])->whereNumber('riderId')->middleware('throttle:60,1')->name('posc.rider.handover');
        Route::post('/deliver-many', [\App\Http\Controllers\PosConsole\PosRiderFlowController::class, 'deliverMany'])->middleware('throttle:60,1')->name('posc.delivermany');
        Route::get('/outlet/current', [\App\Http\Controllers\PosConsole\PosOutletController::class, 'current'])->name('posc.outlet.current');
        Route::post('/outlet/scan', [\App\Http\Controllers\PosConsole\PosOutletController::class, 'scan'])->middleware('throttle:240,1')->name('posc.outlet.scan');
        Route::post('/outlet/sale/{saleId}/item/{itemId}/qty', [\App\Http\Controllers\PosConsole\PosOutletController::class, 'qty'])->whereNumber(['saleId', 'itemId'])->name('posc.outlet.qty');
        Route::post('/outlet/sale/{saleId}/cancel', [\App\Http\Controllers\PosConsole\PosOutletController::class, 'cancel'])->whereNumber('saleId')->name('posc.outlet.cancel');
        Route::post('/outlet/sale/{saleId}/pay', [\App\Http\Controllers\PosConsole\PosOutletController::class, 'pay'])->whereNumber('saleId')->middleware('throttle:60,1')->name('posc.outlet.pay');
        Route::get('/outlet/sale/{saleId}', [\App\Http\Controllers\PosConsole\PosOutletController::class, 'show'])->whereNumber('saleId')->name('posc.outlet.show');
        Route::get('/outlet/summary', [\App\Http\Controllers\PosConsole\PosOutletController::class, 'summary'])->name('posc.outlet.summary');
        Route::get('/outlet/products', [\App\Http\Controllers\PosConsole\PosOutletController::class, 'products'])->name('posc.outlet.products');
        Route::post('/outlet/link', [\App\Http\Controllers\PosConsole\PosOutletController::class, 'link'])->middleware('throttle:60,1')->name('posc.outlet.link');
        Route::get('/outlet/catalog', [\App\Http\Controllers\PosConsole\PosOutletController::class, 'catalog'])->name('posc.outlet.catalog');
        Route::get('/outlet/kiosk', [\App\Http\Controllers\PosConsole\PosOutletController::class, 'kiosk'])->name('posc.outlet.kiosk');
        Route::post('/outlet/kiosk/adopt', [\App\Http\Controllers\PosConsole\PosOutletController::class, 'kioskAdopt'])->middleware('throttle:60,1')->name('posc.outlet.kiosk.adopt');
        Route::get('/invoice/link/{type}/{id}', [\App\Http\Controllers\PosConsole\PosInvoiceController::class, 'link'])->where('type', 'order|outlet')->where('id', '[A-Za-z0-9\-]+')->name('posc.invoice.link');
        Route::get('/invoice/find', [\App\Http\Controllers\PosConsole\PosInvoiceController::class, 'find'])->name('posc.invoice.find');
        Route::post('/order/{orderId}/relocate', [PosConsoleController::class, 'relocate'])->where('orderId', $id)->middleware('throttle:30,1')->name('posc.relocate');
        Route::get('/order/{orderId}/print/{format}', [PosConsoleController::class, 'print'])
            ->where(['orderId' => $id, 'format' => 'thermal|a4|pick'])->name('posc.print');
    });
});
