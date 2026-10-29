<?php

/*
| Freshful admin console: Driver management (drivers update 1, 28 Oct 2026).
| Loaded by one guarded require inside the /admin/console/api/ad group of routes/admin_console.php,
| so every route here needs the admin login. URL /admin/console/api/ad/drivers/*, route names adc.ad.drivers.*
*/

use App\Http\Controllers\AdminConsole\AdminDriversController as D;
use Illuminate\Support\Facades\Route;

Route::prefix('drivers')->group(function () {
    $w = 'throttle:60,1';
    Route::get('/', [D::class, 'list'])->name('adc.ad.drivers');
    Route::get('/counts', [D::class, 'counts'])->name('adc.ad.drivers.counts');
    Route::get('/assign', [D::class, 'assignable'])->name('adc.ad.drivers.assignable');
    Route::get('/assign/{order}/riders', [D::class, 'riders'])->where('order', '[A-Za-z0-9_\-]{1,40}')->name('adc.ad.drivers.riders');
    Route::post('/assign', [D::class, 'assign'])->middleware($w)->name('adc.ad.drivers.assign');
    Route::get('/wallets', [D::class, 'wallets'])->name('adc.ad.drivers.wallets');
    Route::post('/wallets/create-missing', [D::class, 'walletsMissing'])->middleware('throttle:10,1')->name('adc.ad.drivers.wallets.missing');
    Route::post('/fill-names', [D::class, 'fillNames'])->middleware('throttle:10,1')->name('adc.ad.drivers.fill_names');
    Route::get('/{id}', [D::class, 'show'])->whereNumber('id')->name('adc.ad.drivers.show');
    Route::get('/{id}/file/{field}', [D::class, 'file'])->whereNumber('id')->where('field', '[a-z_]{5,30}')->middleware('throttle:300,1')->name('adc.ad.drivers.file');
    Route::post('/{id}/review', [D::class, 'review'])->whereNumber('id')->middleware('throttle:120,1')->name('adc.ad.drivers.review');
    Route::post('/{id}/approve', [D::class, 'approve'])->whereNumber('id')->middleware('throttle:30,1')->name('adc.ad.drivers.approve');
    Route::post('/{id}/reject', [D::class, 'reject'])->whereNumber('id')->middleware('throttle:30,1')->name('adc.ad.drivers.reject');
    Route::post('/{id}/toggle', [D::class, 'toggle'])->whereNumber('id')->middleware('throttle:30,1')->name('adc.ad.drivers.toggle');
    Route::post('/{id}/wallet', [D::class, 'walletMove'])->whereNumber('id')->middleware('throttle:30,1')->name('adc.ad.drivers.wallet');
});
