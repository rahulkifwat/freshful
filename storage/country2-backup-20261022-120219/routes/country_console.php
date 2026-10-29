<?php

/*
| Freshful country console (/country_manager/console). Added 22 Oct 2026.
| Pan-India command centre for the country manager. Loaded by ONE guarded require line at the end of routes/web.php.
| The old country manager panel is untouched. Its dashboard address opens the new console (switch: COUNTRY_CONSOLE_HOME=false);
| the old dashboard page stays reachable at /country_manager/console/classic.
*/

use App\Http\Controllers\CountryConsole\CountryConsoleController as C;
use App\Http\Middleware\CountryConsoleAuth;
use Illuminate\Support\Facades\Route;

// Learn the old panel's login guard from its dashboard route ("auth:<guard>"), and keep that route for the classic view.
$ccOld = null;
foreach (Route::getRoutes()->getRoutes() as $ccR) {
    if (trim($ccR->uri(), '/') === 'country_manager/dashboard' && in_array('GET', $ccR->methods(), true)) { $ccOld = $ccR; break; }
}
if ($ccOld) {
    C::$classic = $ccOld;
    foreach ((array) $ccOld->middleware() as $ccM) {
        if (is_string($ccM) && preg_match('/^auth:([\w\-]+)/', $ccM, $ccMatch)) { CountryConsoleAuth::$fromRoute = $ccMatch[1]; break; }
    }
}

Route::middleware([CountryConsoleAuth::class, 'throttle:240,1'])->prefix('country_manager/console')->group(function () {
    Route::get('/', [C::class, 'app'])->name('ccc.home');
    Route::get('/classic', [C::class, 'classic'])->name('ccc.classic');
    Route::prefix('api')->group(function () {
        Route::get('/meta', [C::class, 'meta'])->name('ccc.meta');
        Route::get('/overview', [C::class, 'overview'])->name('ccc.overview');
        Route::get('/live', [C::class, 'live'])->name('ccc.live');
        Route::get('/orders', [C::class, 'orders'])->name('ccc.orders');
        Route::get('/orders/cancellations', [C::class, 'cancels'])->name('ccc.cancels');
        Route::get('/order/{id}', [C::class, 'order'])->where('id', '[A-Za-z0-9_\-]{1,40}')->name('ccc.order');
        Route::post('/order/{id}/cancel', [C::class, 'orderCancel'])->where('id', '[A-Za-z0-9_\-]{1,40}')->middleware('throttle:30,1')->name('ccc.order.cancel');
        Route::post('/order/{id}/move', [C::class, 'orderMove'])->where('id', '[A-Za-z0-9_\-]{1,40}')->middleware('throttle:30,1')->name('ccc.order.move');
        Route::get('/stock/products', [C::class, 'stockProducts'])->name('ccc.stock.products');
        Route::get('/stock/product/{id}', [C::class, 'stockProduct'])->whereNumber('id')->name('ccc.stock.product');
        Route::get('/stock/hubs', [C::class, 'stockHubs'])->name('ccc.stock.hubs');
        Route::get('/stock/hub/{hub}', [C::class, 'hubProducts'])->whereNumber('hub')->name('ccc.stock.hub');
        Route::get('/stock/danger', [C::class, 'danger'])->name('ccc.danger');
        Route::post('/stock/danger/plan', [C::class, 'dangerPlan'])->middleware('throttle:10,1')->name('ccc.danger.plan');
        Route::get('/stock/inward', [C::class, 'inward'])->name('ccc.inward');
        Route::get('/stock/ledger', [C::class, 'ledger'])->name('ccc.ledger');
        Route::post('/stock/count', [C::class, 'count'])->middleware('throttle:60,1')->name('ccc.count');
        Route::get('/wastage', [C::class, 'wastage'])->name('ccc.wastage');
        Route::post('/wastage', [C::class, 'writeOff'])->middleware('throttle:60,1')->name('ccc.writeoff');
        Route::get('/transfers', [C::class, 'transfers'])->name('ccc.transfers');
        Route::post('/transfers', [C::class, 'transferCreate'])->middleware('throttle:30,1')->name('ccc.transfer.create');
        Route::post('/transfers/{id}/cancel', [C::class, 'transferCancel'])->whereNumber('id')->middleware('throttle:30,1')->name('ccc.transfer.cancel');
        Route::get('/cash', [C::class, 'cash'])->name('ccc.cash');
        Route::get('/riders', [C::class, 'riders'])->name('ccc.riders');
        Route::get('/customers', [C::class, 'customers'])->name('ccc.customers');
        Route::get('/customers/{id}', [C::class, 'customer'])->whereNumber('id')->name('ccc.customer');
        Route::get('/grievances', [C::class, 'grievances'])->name('ccc.grievances');
        Route::get('/reports/sales', [C::class, 'sales'])->name('ccc.sales');
        Route::get('/targets', [C::class, 'targets'])->name('ccc.targets');
        Route::post('/targets', [C::class, 'targetSave'])->middleware('throttle:30,1')->name('ccc.target.save');
        Route::post('/targets/{id}/delete', [C::class, 'targetDelete'])->whereNumber('id')->middleware('throttle:30,1')->name('ccc.target.delete');
    });
});

// The old dashboard address opens the new console; same route name, so every existing link and the login redirect keep working.
if ($ccOld && config('country_console.replace_dashboard', true)) {
    $ccNew = Route::get('country_manager/dashboard', fn () => redirect()->route('ccc.home'))->middleware(array_values(array_diff((array) $ccOld->middleware(), ['web'])));
    if ($ccOld->getName()) $ccNew->name($ccOld->getName());
}
unset($ccOld, $ccR, $ccM, $ccMatch, $ccNew);
