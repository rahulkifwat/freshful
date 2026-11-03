<?php

use App\Http\Controllers\Api\BuyerApp\AccountController;
use App\Http\Controllers\Api\BuyerApp\AuthController;
use App\Http\Controllers\Api\BuyerApp\ConfigController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer app API (Expo) - loaded by BuyerAppServiceProvider under /api/app
|--------------------------------------------------------------------------
| Public:  config, otp request/verify, password login.
| Signed in (Authorization: Bearer <token from verify>): me, account/*.
| Other app calls (catalog, cart, address, orders) stay on the existing /api/* routes.
*/

Route::get('config', [ConfigController::class, 'show']);

Route::post('auth/otp/request', [AuthController::class, 'requestOtp'])->middleware('throttle:otp-send');
Route::post('auth/otp/verify', [AuthController::class, 'verifyOtp'])->middleware('throttle:otp-verify');
Route::post('auth/password', [AuthController::class, 'passwordLogin']);

Route::middleware('buyer.api')->group(function () {
    Route::get('me', [AccountController::class, 'me']);
    Route::post('account/complete', [AccountController::class, 'complete']);
    Route::post('account/password', [AccountController::class, 'setPassword']);
    Route::post('account/delete', [AccountController::class, 'deleteAccount']);
});
