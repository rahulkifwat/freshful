<?php

use App\Http\Controllers\Api\BuyerApp\AddressesController;
use App\Http\Controllers\Api\BuyerApp\PaymentMethodsController;
use App\Http\Controllers\Api\BuyerApp\ServiceAreaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer app API, commerce part - loaded by BuyerAppCommerceServiceProvider under /api/app
|--------------------------------------------------------------------------
| Signed in (Authorization: Bearer <token>):
|   GET  service-area?lat=&lng=     do we deliver to this point, which hub, what it costs
|   GET  payment-methods            the payment methods that are really available right now
|   GET  addresses                  saved addresses, each with covered / hub worked out on the server
|   POST addresses                  add an address (hub chosen by the server from the coordinates)
|   POST addresses/{id}             change an address
|   POST addresses/{id}/delete      remove an address from the customer's list
*/

Route::middleware('buyer.api')->group(function () {
    Route::get('service-area', [ServiceAreaController::class, 'check'])->middleware('throttle:60,1,apparea');
    Route::get('payment-methods', [PaymentMethodsController::class, 'index'])->middleware('throttle:60,1,apppay');

    Route::get('addresses', [AddressesController::class, 'index'])->middleware('throttle:60,1,appaddrlist');
    Route::post('addresses', [AddressesController::class, 'store'])->middleware('throttle:20,1,appaddrsave');
    Route::post('addresses/{id}', [AddressesController::class, 'update'])->where('id', '[0-9]+')->middleware('throttle:20,1,appaddrsave');
    Route::post('addresses/{id}/delete', [AddressesController::class, 'destroy'])->where('id', '[0-9]+')->middleware('throttle:20,1,appaddrdel');
});
