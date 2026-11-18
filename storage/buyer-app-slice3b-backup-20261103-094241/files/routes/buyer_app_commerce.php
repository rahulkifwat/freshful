<?php

use App\Http\Controllers\Api\BuyerApp\PaymentMethodsController;
use App\Http\Controllers\Api\BuyerApp\ServiceAreaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer app API, commerce part - loaded by BuyerAppCommerceServiceProvider under /api/app
|--------------------------------------------------------------------------
| Signed in (Authorization: Bearer <token>):
|   GET service-area?lat=&lng=   do we deliver to this point, which hub, what it costs
|   GET payment-methods          the payment methods that are really available right now
*/

Route::middleware('buyer.api')->group(function () {
    Route::get('service-area', [ServiceAreaController::class, 'check'])->middleware('throttle:60,1,apparea');
    Route::get('payment-methods', [PaymentMethodsController::class, 'index'])->middleware('throttle:60,1,apppay');
});
