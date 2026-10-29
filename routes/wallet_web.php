<?php

/*
| Freshful customer wallet top-up (web). Added 21 Oct 2026.
| The My Account page (resources/views/myaccount.blade.php) calls these two routes;
| WalletController itself refuses anyone who is not logged in.
| Loaded by ONE guarded require line at the end of routes/web.php.
*/

use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->middleware('throttle:20,1')->group(function () {
    Route::post('/wallet/topup/order',  [WalletController::class, 'createTopupOrder'])->name('user.wallet.topup.order');
    Route::post('/wallet/topup/verify', [WalletController::class, 'verifyTopup'])->name('user.wallet.topup.verify');
});
