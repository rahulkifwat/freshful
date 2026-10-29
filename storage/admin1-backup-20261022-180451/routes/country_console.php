<?php

/*
| Freshful regional consoles. Added 22 Oct 2026, update 2 (area + operation managers).
|   /country_manager/console    - country manager, all of India              (route names ccc.*)
|   /area_manager/console       - area manager, only the hubs in area_managers.hub_id (cca.*)
|   /operation_manager/console  - operation manager (cco.*)
| One controller and one page; what each person sees is limited by CountryConsoleAuth (CountryScope::$allowed).
| Loaded by ONE guarded require line at the end of routes/web.php. The old panels are untouched: their dashboard
| address opens the console (switches in config/country_console.php), and the old page stays at <prefix>/console/classic.
*/

use App\Http\Controllers\CountryConsole\CountryConsoleController as C;
use App\Http\Middleware\CountryConsoleAuth;
use Illuminate\Support\Facades\Route;

foreach (CountryConsoleAuth::ROLES as $ccRole => $ccCfg) {
    $ccPrefix = $ccCfg['prefix']; $ccN = $ccCfg['names'];
    // The old panel's dashboard route: its "auth:<guard>" tells us the login guard; it is kept for the classic view.
    $ccOld = null; $ccHome = null;
    foreach (Route::getRoutes()->getRoutes() as $ccR) {
        if (!in_array('GET', $ccR->methods(), true)) continue;
        $u = trim($ccR->uri(), '/');
        if ($u === $ccPrefix . '/dashboard') $ccOld = $ccR;
        if ($u === $ccPrefix) $ccHome = $ccR;
    }
    if ($ccOld) {
        C::$classic[$ccRole] = $ccOld;
        foreach ((array) $ccOld->middleware() as $ccM) {
            if (is_string($ccM) && preg_match('/^auth:([\w\-]+)/', $ccM, $ccMatch)) { CountryConsoleAuth::$fromRoute[$ccRole] = $ccMatch[1]; break; }
        }
    }

    Route::middleware([CountryConsoleAuth::class . ':' . $ccRole, 'throttle:240,1'])->prefix($ccPrefix . '/console')->group(function () use ($ccN) {
        Route::get('/', [C::class, 'app'])->name($ccN . 'home');
        Route::get('/classic', [C::class, 'classic'])->name($ccN . 'classic');
        Route::prefix('api')->group(function () use ($ccN) {
            $id = '[A-Za-z0-9_\-]{1,40}';
            Route::get('/meta', [C::class, 'meta'])->name($ccN . 'meta');
            Route::get('/overview', [C::class, 'overview'])->name($ccN . 'overview');
            Route::get('/live', [C::class, 'live'])->name($ccN . 'live');
            Route::get('/orders', [C::class, 'orders'])->name($ccN . 'orders');
            Route::get('/orders/cancellations', [C::class, 'cancels'])->name($ccN . 'cancels');
            Route::get('/order/{id}', [C::class, 'order'])->where('id', $id)->name($ccN . 'order');
            Route::post('/order/{id}/cancel', [C::class, 'orderCancel'])->where('id', $id)->middleware('throttle:30,1')->name($ccN . 'order.cancel');
            Route::post('/order/{id}/move', [C::class, 'orderMove'])->where('id', $id)->middleware('throttle:30,1')->name($ccN . 'order.move');
            Route::get('/stock/products', [C::class, 'stockProducts'])->name($ccN . 'stock.products');
            Route::get('/stock/product/{id}', [C::class, 'stockProduct'])->whereNumber('id')->name($ccN . 'stock.product');
            Route::get('/stock/hubs', [C::class, 'stockHubs'])->name($ccN . 'stock.hubs');
            Route::get('/stock/hub/{hub}', [C::class, 'hubProducts'])->whereNumber('hub')->name($ccN . 'stock.hub');
            Route::get('/stock/sheet', [C::class, 'sheet'])->name($ccN . 'sheet');
            Route::get('/stock/danger', [C::class, 'danger'])->name($ccN . 'danger');
            Route::post('/stock/danger/plan', [C::class, 'dangerPlan'])->middleware('throttle:10,1')->name($ccN . 'danger.plan');
            Route::get('/stock/inward', [C::class, 'inward'])->name($ccN . 'inward');
            Route::post('/stock/accept', [C::class, 'acceptInward'])->middleware('throttle:60,1')->name($ccN . 'accept');
            Route::get('/stock/ledger', [C::class, 'ledger'])->name($ccN . 'ledger');
            Route::post('/stock/count', [C::class, 'count'])->middleware('throttle:60,1')->name($ccN . 'count');
            Route::get('/wastage', [C::class, 'wastage'])->name($ccN . 'wastage');
            Route::post('/wastage', [C::class, 'writeOff'])->middleware('throttle:60,1')->name($ccN . 'writeoff');
            Route::get('/transfers', [C::class, 'transfers'])->name($ccN . 'transfers');
            Route::post('/transfers', [C::class, 'transferCreate'])->middleware('throttle:30,1')->name($ccN . 'transfer.create');
            Route::post('/transfers/send', [C::class, 'transferSend'])->middleware('throttle:30,1')->name($ccN . 'transfer.send');
            Route::post('/transfers/{id}/cancel', [C::class, 'transferCancel'])->whereNumber('id')->middleware('throttle:30,1')->name($ccN . 'transfer.cancel');
            Route::post('/transfers/{id}/dispatch', [C::class, 'transferDispatch'])->whereNumber('id')->middleware('throttle:30,1')->name($ccN . 'transfer.dispatch');
            Route::post('/transfers/{id}/receive', [C::class, 'transferReceive'])->whereNumber('id')->middleware('throttle:30,1')->name($ccN . 'transfer.receive');
            Route::post('/transfers/{id}/decline', [C::class, 'transferDecline'])->whereNumber('id')->middleware('throttle:30,1')->name($ccN . 'transfer.decline');
            Route::get('/cash', [C::class, 'cash'])->name($ccN . 'cash');
            Route::get('/riders', [C::class, 'riders'])->name($ccN . 'riders');
            Route::get('/riders/hub/{hub}', [C::class, 'hubRiders'])->whereNumber('hub')->name($ccN . 'riders.hub');
            Route::get('/riders/performance', [C::class, 'riderPerformance'])->name($ccN . 'riders.performance');
            Route::get('/riders/{id}/history', [C::class, 'riderHistory'])->whereNumber('id')->name($ccN . 'rider.history');
            Route::post('/riders', [C::class, 'riderSave'])->middleware('throttle:30,1')->name($ccN . 'rider.save');
            Route::post('/riders/{id}/toggle', [C::class, 'riderToggle'])->whereNumber('id')->middleware('throttle:30,1')->name($ccN . 'rider.toggle');
            Route::post('/riders/{id}/move', [C::class, 'riderMove'])->whereNumber('id')->middleware('throttle:30,1')->name($ccN . 'rider.move');
            Route::get('/customers', [C::class, 'customers'])->name($ccN . 'customers');
            Route::get('/customers/{id}', [C::class, 'customer'])->whereNumber('id')->name($ccN . 'customer');
            Route::get('/wallets', [C::class, 'wallets'])->name($ccN . 'wallets');
            Route::get('/grievances', [C::class, 'grievances'])->name($ccN . 'grievances');
            Route::get('/reports/sales', [C::class, 'sales'])->name($ccN . 'sales');
            Route::get('/targets', [C::class, 'targets'])->name($ccN . 'targets');
            Route::post('/targets', [C::class, 'targetSave'])->middleware('throttle:30,1')->name($ccN . 'target.save');
            Route::post('/targets/{id}/delete', [C::class, 'targetDelete'])->whereNumber('id')->middleware('throttle:30,1')->name($ccN . 'target.delete');
        });
    });

    // The panel's dashboard and home addresses open the console; same route names, so every existing link and the login redirect keep working.
    if (config('country_console.replace_dashboard.' . $ccRole, true)) {
        foreach ([$ccOld, $ccHome] as $ccX) {
            if (!$ccX) continue;
            $ccNew = Route::get($ccX->uri(), fn () => redirect()->route($ccN . 'home'))->middleware(array_values(array_diff((array) $ccX->middleware(), ['web'])));
            if ($ccX->getName()) $ccNew->name($ccX->getName());
        }
    }
}
unset($ccRole, $ccCfg, $ccPrefix, $ccN, $ccOld, $ccHome, $ccR, $ccM, $ccMatch, $ccNew, $ccX, $u);
