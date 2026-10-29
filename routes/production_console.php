<?php

/*
| Freshful production console (production update 1, 28 Oct 2026): /production/console (route names ccp.*).
| Loaded by ONE guarded require line at the end of routes/web.php. The production login (production/login) is unchanged.
| production/dashboard opens the console (switch PRODUCTION_CONSOLE_HOME); the old page stays at production/console/classic.
| Shared screens run the country console code; ProductionConsoleAuth limits every API call to the production role's permissions.
*/

use App\Http\Controllers\CountryConsole\CountryConsoleController as CC;
use App\Http\Controllers\ProductionConsole\ProductionConsoleController as P;
use App\Http\Middleware\ProductionConsoleAuth;
use Illuminate\Support\Facades\Route;

if (class_exists(P::class) && class_exists(ProductionConsoleAuth::class)) {
    $prOld = null; $prHome = null;
    foreach (Route::getRoutes()->getRoutes() as $prR) {
        if (!in_array('GET', $prR->methods(), true)) continue;
        $prU = trim($prR->uri(), '/');
        if ($prU === 'production/dashboard') $prOld = $prR;
        if ($prU === 'production') $prHome = $prR;
    }
    if ($prOld) CC::$classic['production'] = $prOld;

    Route::middleware([ProductionConsoleAuth::class, 'throttle:240,1'])->prefix('production/console')->group(function () {
        Route::get('/', [P::class, 'app'])->name('ccp.home');
        Route::get('/classic', [P::class, 'classic'])->name('ccp.classic');
        Route::prefix('api')->group(function () {
            $id = '[A-Za-z0-9_\-]{1,40}';
            $n = 'ccp.';
            // production screens
            Route::get('/pr/home', [P::class, 'prHome'])->name($n . 'pr.home');
            Route::get('/pr/plan', [P::class, 'prPlan'])->name($n . 'pr.plan');
            Route::post('/pr/plan', [P::class, 'prPlanSave'])->middleware('throttle:60,1,ccp-pr.plan.save')->name($n . 'pr.plan.save');
            Route::get('/pr/sheet', [P::class, 'prSheet'])->name($n . 'pr.sheet');
            Route::post('/pr/send', [P::class, 'prSend'])->middleware('throttle:20,1,ccp-pr.send')->name($n . 'pr.send');
            Route::get('/pr/batches', [P::class, 'prBatches'])->name($n . 'pr.batches');
            Route::get('/pr/batch/{id}', [P::class, 'prBatch'])->whereNumber('id')->name($n . 'pr.batch');
            Route::post('/pr/line/{id}/cancel', [P::class, 'prLineCancel'])->whereNumber('id')->middleware('throttle:30,1,ccp-pr.line.cancel')->name($n . 'pr.line.cancel');
            Route::get('/pr/receipts', [P::class, 'prReceipts'])->name($n . 'pr.receipts');
            Route::get('/pr/board', [P::class, 'prBoard'])->name($n . 'pr.board');
            Route::get('/pr/stale', [P::class, 'prStale'])->name($n . 'pr.stale');
            Route::post('/pr/stale/clear', [P::class, 'prStaleClear'])->middleware('throttle:10,1,ccp-pr.stale.clear')->name($n . 'pr.stale.clear');
            Route::post('/pr/stale/{id}/restore', [P::class, 'prStaleRestore'])->whereNumber('id')->middleware('throttle:30,1,ccp-pr.stale.restore')->name($n . 'pr.stale.restore');
            Route::get('/pr/products', [P::class, 'prProducts'])->name($n . 'pr.products');
            Route::post('/pr/products/{id}/toggle', [P::class, 'prProductToggle'])->whereNumber('id')->middleware('throttle:60,1,ccp-pr.product.toggle')->name($n . 'pr.product.toggle');
            // shared console screens (same addresses as the other consoles; ProductionAccess decides who may use them)
            Route::get('/meta', [P::class, 'meta'])->name($n . 'meta');
            Route::get('/orders', [P::class, 'orders'])->name($n . 'orders');
            Route::get('/orders/cancellations', [P::class, 'cancels'])->name($n . 'cancels');
            Route::get('/order/{id}', [P::class, 'order'])->where('id', $id)->name($n . 'order');
            Route::get('/stock/products', [P::class, 'stockProducts'])->name($n . 'stock.products');
            Route::get('/stock/product/{id}', [P::class, 'stockProduct'])->whereNumber('id')->name($n . 'stock.product');
            Route::get('/stock/hubs', [P::class, 'stockHubs'])->name($n . 'stock.hubs');
            Route::get('/stock/hub/{hub}', [P::class, 'hubProducts'])->whereNumber('hub')->name($n . 'stock.hub');
            Route::get('/stock/sheet', [P::class, 'sheet'])->name($n . 'sheet');
            Route::get('/stock/danger', [P::class, 'danger'])->name($n . 'danger');
            Route::post('/stock/danger/plan', [P::class, 'dangerPlan'])->middleware('throttle:10,1,ccp-danger.plan')->name($n . 'danger.plan');
            Route::get('/stock/inward', [P::class, 'inward'])->name($n . 'inward');
            Route::post('/stock/accept', [P::class, 'acceptInward'])->middleware('throttle:60,1,ccp-accept')->name($n . 'accept');
            Route::get('/stock/ledger', [P::class, 'ledger'])->name($n . 'ledger');
            Route::post('/stock/count', [P::class, 'count'])->middleware('throttle:60,1,ccp-count')->name($n . 'count');
            Route::get('/wastage', [P::class, 'wastage'])->name($n . 'wastage');
            Route::post('/wastage', [P::class, 'writeOff'])->middleware('throttle:60,1,ccp-writeoff')->name($n . 'writeoff');
            Route::get('/transfers', [P::class, 'transfers'])->name($n . 'transfers');
            Route::post('/transfers', [P::class, 'transferCreate'])->middleware('throttle:30,1,ccp-transfer.create')->name($n . 'transfer.create');
            Route::post('/transfers/send', [P::class, 'transferSend'])->middleware('throttle:30,1,ccp-transfer.send')->name($n . 'transfer.send');
            Route::post('/transfers/{id}/cancel', [P::class, 'transferCancel'])->whereNumber('id')->middleware('throttle:30,1,ccp-transfer.cancel')->name($n . 'transfer.cancel');
            Route::post('/transfers/{id}/dispatch', [P::class, 'transferDispatch'])->whereNumber('id')->middleware('throttle:30,1,ccp-transfer.dispatch')->name($n . 'transfer.dispatch');
            Route::post('/transfers/{id}/receive', [P::class, 'transferReceive'])->whereNumber('id')->middleware('throttle:30,1,ccp-transfer.receive')->name($n . 'transfer.receive');
            Route::post('/transfers/{id}/decline', [P::class, 'transferDecline'])->whereNumber('id')->middleware('throttle:30,1,ccp-transfer.decline')->name($n . 'transfer.decline');
            Route::get('/cash', [P::class, 'cash'])->name($n . 'cash');
            Route::get('/riders', [P::class, 'riders'])->name($n . 'riders');
            Route::get('/riders/hub/{hub}', [P::class, 'hubRiders'])->whereNumber('hub')->name($n . 'riders.hub');
            Route::get('/riders/performance', [P::class, 'riderPerformance'])->name($n . 'riders.performance');
            Route::get('/riders/{id}/history', [P::class, 'riderHistory'])->whereNumber('id')->name($n . 'rider.history');
            Route::post('/riders', [P::class, 'riderSave'])->middleware('throttle:30,1,ccp-rider.save')->name($n . 'rider.save');
            Route::post('/riders/{id}/toggle', [P::class, 'riderToggle'])->whereNumber('id')->middleware('throttle:30,1,ccp-rider.toggle')->name($n . 'rider.toggle');
            Route::post('/riders/{id}/move', [P::class, 'riderMove'])->whereNumber('id')->middleware('throttle:30,1,ccp-rider.move')->name($n . 'rider.move');
            Route::get('/customers', [P::class, 'customers'])->name($n . 'customers');
            Route::get('/customers/{id}', [P::class, 'customer'])->whereNumber('id')->name($n . 'customer');
            Route::get('/wallets', [P::class, 'wallets'])->name($n . 'wallets');
            Route::get('/grievances', [P::class, 'grievances'])->name($n . 'grievances');
            Route::get('/reports/sales', [P::class, 'sales'])->name($n . 'sales');
            // order cancel / move and targets are never allowed for production (ProductionAccess::NEVER); they are not even routed.
        });
    });

    // production/dashboard (and /production) open the console; same route names, so the login redirect and every old link keep working.
    if (config('production_console.replace_dashboard', true)) {
        foreach ([$prOld, $prHome] as $prX) {
            if (!$prX) continue;
            $prNew = Route::get($prX->uri(), fn () => redirect()->route('ccp.home'))->middleware(array_values(array_diff((array) $prX->middleware(), ['web'])));
            if ($prX->getName()) $prNew->name($prX->getName());
        }
    }
    // production/products crashed in the old Laravel panel (its view was never created); it now opens the console's Products screen.
    foreach (Route::getRoutes()->getRoutes() as $prR) {
        if (in_array('GET', $prR->methods(), true) && trim($prR->uri(), '/') === 'production/products') {
            $prNew = Route::get($prR->uri(), fn () => redirect()->to(route('ccp.home') . '#pr-products'))->middleware(array_values(array_diff((array) $prR->middleware(), ['web'])));
            if ($prR->getName()) $prNew->name($prR->getName());
            break;
        }
    }
    unset($prOld, $prHome, $prR, $prU, $prX, $prNew);
}
