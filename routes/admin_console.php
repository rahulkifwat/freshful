<?php

/*
| Freshful admin console (admin update 1, 22 Oct 2026). Loaded INSIDE the /admin/console/api group of
| routes/country_console.php, only for the admin role, so every route here needs the admin login.
| Route names: adc.ad.*
*/

use App\Http\Controllers\AdminConsole\AdminConsoleController as A;
use Illuminate\Support\Facades\Route;

Route::prefix('ad')->group(function () {
    $w = 'throttle:60,1';
    Route::get('/home', [A::class, 'home'])->name('adc.ad.home');
    Route::get('/meta', [A::class, 'meta'])->name('adc.ad.meta');
    Route::post('/upload', [A::class, 'upload'])->middleware('throttle:40,1')->name('adc.ad.upload');

    Route::get('/r/{res}', [A::class, 'records'])->where('res', '[a-z]+')->name('adc.ad.records');
    Route::post('/r/{res}/save', [A::class, 'recordSave'])->where('res', '[a-z]+')->middleware($w)->name('adc.ad.record.save');
    Route::post('/r/{res}/{id}/toggle', [A::class, 'recordToggle'])->where('res', '[a-z]+')->whereNumber('id')->middleware($w)->name('adc.ad.record.toggle');
    Route::post('/r/{res}/{id}/delete', [A::class, 'recordDelete'])->where('res', '[a-z]+')->whereNumber('id')->middleware('throttle:30,1')->name('adc.ad.record.delete');

    Route::get('/products', [A::class, 'products'])->name('adc.ad.products');
    Route::get('/products/meta', [A::class, 'productMeta'])->name('adc.ad.products.meta');
    Route::get('/products/items/{cat}', [A::class, 'productItems'])->whereNumber('cat')->name('adc.ad.products.items');
    Route::get('/products/{id}', [A::class, 'product'])->whereNumber('id')->name('adc.ad.product');
    Route::post('/products/save', [A::class, 'productSave'])->middleware($w)->name('adc.ad.product.save');
    Route::post('/products/{id}/toggle', [A::class, 'productToggle'])->whereNumber('id')->middleware($w)->name('adc.ad.product.toggle');
    Route::post('/products/bulk-status', [A::class, 'productBulk'])->middleware('throttle:20,1')->name('adc.ad.product.bulk');
    Route::get('/products/missing', [A::class, 'missing'])->name('adc.ad.missing');
    Route::get('/products/export', [A::class, 'productExport'])->name('adc.ad.product.export');
    Route::get('/products/template', [A::class, 'productTemplate'])->name('adc.ad.product.template');
    Route::post('/products/excel', [A::class, 'productExcel'])->middleware('throttle:20,1')->name('adc.ad.product.excel');

    Route::get('/staff', [A::class, 'staffMeta'])->name('adc.ad.staff');
    Route::get('/staff/{type}', [A::class, 'staff'])->where('type', '[a-z]+')->name('adc.ad.staff.list');
    Route::post('/staff/{type}/save', [A::class, 'staffSave'])->where('type', '[a-z]+')->middleware('throttle:30,1')->name('adc.ad.staff.save');
    Route::post('/staff/{type}/{id}/toggle', [A::class, 'staffToggle'])->where('type', '[a-z]+')->whereNumber('id')->middleware('throttle:30,1')->name('adc.ad.staff.toggle');
    Route::get('/roles', [A::class, 'roles'])->name('adc.ad.roles');
    Route::post('/roles/save', [A::class, 'roleSave'])->middleware('throttle:30,1')->name('adc.ad.role.save');

    Route::get('/hubs', [A::class, 'hubs'])->name('adc.ad.hubs');
    Route::post('/hubs/save', [A::class, 'hubSave'])->middleware('throttle:30,1')->name('adc.ad.hub.save');
    Route::post('/hubs/{id}/toggle', [A::class, 'hubToggle'])->whereNumber('id')->middleware('throttle:30,1')->name('adc.ad.hub.toggle');

    Route::get('/push', [A::class, 'push'])->name('adc.ad.push');
    Route::post('/push', [A::class, 'pushSend'])->middleware('throttle:5,1')->name('adc.ad.push.send');

    Route::get('/reports/inventory', [A::class, 'invReport'])->name('adc.ad.report.inventory');
    Route::get('/audit', [A::class, 'audit'])->name('adc.ad.audit');

    // admin update 2
    Route::get('/cities-of/{state}', [A::class, 'citiesOf'])->whereNumber('state')->name('adc.ad.cities.of');
    Route::post('/products/{id}/delete', [A::class, 'productDelete'])->whereNumber('id')->middleware('throttle:20,1')->name('adc.ad.product.delete');
    Route::get('/live/god', [A::class, 'god'])->name('adc.ad.god');
    Route::get('/live/heat', [A::class, 'heat'])->name('adc.ad.heat');
    Route::get('/live/dispatch', [A::class, 'board'])->name('adc.ad.dispatch');
    Route::post('/live/dispatch/act', [A::class, 'boardAct'])->middleware('throttle:60,1')->name('adc.ad.dispatch.act');
    Route::get('/refunds', [A::class, 'refunds'])->name('adc.ad.refunds');
    Route::post('/refunds/pay', [A::class, 'refundPay'])->middleware('throttle:30,1')->name('adc.ad.refund.pay');
    Route::post('/refunds/reject', [A::class, 'refundReject'])->middleware('throttle:30,1')->name('adc.ad.refund.reject');
    Route::get('/tickets', [A::class, 'tickets'])->name('adc.ad.tickets');
    Route::post('/tickets', [A::class, 'ticketCreate'])->middleware('throttle:30,1')->name('adc.ad.ticket.create');
    Route::get('/tickets/{id}', [A::class, 'ticket'])->whereNumber('id')->name('adc.ad.ticket');
    Route::post('/tickets/{id}/reply', [A::class, 'ticketReply'])->whereNumber('id')->middleware('throttle:60,1')->name('adc.ad.ticket.reply');
    Route::post('/tickets/{id}/update', [A::class, 'ticketUpdate'])->whereNumber('id')->middleware('throttle:60,1')->name('adc.ad.ticket.update');
    Route::post('/tickets/{id}/settle', [A::class, 'ticketSettle'])->whereNumber('id')->middleware('throttle:20,1')->name('adc.ad.ticket.settle');
    Route::get('/enquiries/{type}', [A::class, 'enquiries'])->where('type', 'contact|franchise')->name('adc.ad.enquiries');
    Route::post('/enquiries/{type}/{id}/ticket', [A::class, 'enquiryTicket'])->where('type', 'contact|franchise')->whereNumber('id')->middleware('throttle:30,1')->name('adc.ad.enquiry.ticket');
    Route::get('/reviews', [A::class, 'reviews'])->name('adc.ad.reviews');
    Route::post('/reviews/{id}', [A::class, 'reviewSave'])->whereNumber('id')->middleware('throttle:60,1')->name('adc.ad.review.save');

    Route::get('/profile', [A::class, 'profile'])->name('adc.ad.profile');
    Route::post('/profile', [A::class, 'profileSave'])->middleware('throttle:20,1')->name('adc.ad.profile.save');
    Route::post('/profile/password', [A::class, 'password'])->middleware('throttle:6,1')->name('adc.ad.profile.password');

    // Driver management (drivers update 1, 28 Oct 2026)
    if (file_exists(__DIR__ . '/admin_drivers.php')) require __DIR__ . '/admin_drivers.php';
});
