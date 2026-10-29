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

    // Phase 2 (22 Oct 2026): locked stock, stock ledger, transfers, cash book, riders, customers.
    Route::get('/api/locked', [HubConsoleController::class, 'locked'])->name('hubc.locked');
    Route::get('/api/ledger', [HubConsoleController::class, 'ledger'])->name('hubc.ledger');
    Route::post('/api/inward-many', [HubConsoleController::class, 'inwardMany'])->middleware('throttle:30,1')->name('hubc.inward.many');
    Route::get('/api/transfers', [HubConsoleController::class, 'transfers'])->name('hubc.transfers');
    Route::get('/api/network', [HubConsoleController::class, 'network'])->name('hubc.network');
    Route::post('/api/transfers/send', [HubConsoleController::class, 'transferSend'])->middleware('throttle:30,1')->name('hubc.transfer.send');
    Route::post('/api/transfers/request', [HubConsoleController::class, 'transferRequest'])->middleware('throttle:30,1')->name('hubc.transfer.request');
    Route::post('/api/transfers/{id}/fulfil', [HubConsoleController::class, 'transferFulfil'])->whereNumber('id')->middleware('throttle:30,1')->name('hubc.transfer.fulfil');
    Route::post('/api/transfers/{id}/decline', [HubConsoleController::class, 'transferDecline'])->whereNumber('id')->middleware('throttle:30,1')->name('hubc.transfer.decline');
    Route::post('/api/transfers/{id}/cancel', [HubConsoleController::class, 'transferCancel'])->whereNumber('id')->middleware('throttle:30,1')->name('hubc.transfer.cancel');
    Route::post('/api/transfers/{id}/receive', [HubConsoleController::class, 'transferReceive'])->whereNumber('id')->middleware('throttle:30,1')->name('hubc.transfer.receive');
    Route::get('/api/cash', [HubConsoleController::class, 'cashBook'])->name('hubc.cash');
    Route::post('/api/cash', [HubConsoleController::class, 'cashAdd'])->middleware('throttle:30,1')->name('hubc.cash.add');
    Route::post('/api/cash/{id}/void', [HubConsoleController::class, 'cashVoid'])->whereNumber('id')->middleware('throttle:30,1')->name('hubc.cash.void');
    Route::get('/cash/receipt/{id}', [HubConsoleController::class, 'receipt'])->whereNumber('id')->name('hubc.receipt');
    Route::get('/api/riders', [HubConsoleController::class, 'riders'])->name('hubc.riders');
    Route::post('/api/riders', [HubConsoleController::class, 'riderSave'])->middleware('throttle:30,1')->name('hubc.rider.save');
    Route::post('/api/riders/{id}/toggle', [HubConsoleController::class, 'riderToggle'])->whereNumber('id')->middleware('throttle:30,1')->name('hubc.rider.toggle');
    Route::get('/api/customers', [HubConsoleController::class, 'customers'])->name('hubc.customers');
    Route::get('/api/customers/{id}', [HubConsoleController::class, 'customer'])->whereNumber('id')->name('hubc.customer');
    // Phase 3 (22 Oct 2026)
    Route::get('/api/grievances', [HubConsoleController::class, 'grievances'])->name('hubc.grievances');
});

// The old dashboard address opens the new console (same name, so every existing link keeps working).
Route::middleware([HubConsoleAuth::class])->get('hub/dashboard', fn () => redirect()->route('hubc.home'))->name('hub.dashboard');
