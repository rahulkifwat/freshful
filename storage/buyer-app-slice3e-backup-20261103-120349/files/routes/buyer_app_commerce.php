<?php

use App\Http\Controllers\Api\BuyerApp\AddressesController;
use App\Http\Controllers\Api\BuyerApp\CheckoutController;
use App\Http\Controllers\Api\BuyerApp\CouponsController;
use App\Http\Controllers\Api\BuyerApp\PaymentMethodsController;
use App\Http\Controllers\Api\BuyerApp\PaymentsController;
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
|   GET  coupons                     coupons with eligibility and savings for the current cart
|   GET  checkout/slots              delivery times (Express and slots)
|   POST checkout/quote              full price breakdown from the server cart, nothing is saved
|   POST orders                      place an order; every amount is worked out on the server (cash, card on delivery, wallet)
|   POST payments/razorpay/create   start an online payment for the cart
|   POST payments/razorpay/confirm  verify the payment and place the order
|   GET  payments/razorpay/status   recover a payment whose confirmation never arrived
*/

Route::middleware('buyer.api')->group(function () {
    Route::get('service-area', [ServiceAreaController::class, 'check'])->middleware('throttle:60,1,apparea');
    Route::get('payment-methods', [PaymentMethodsController::class, 'index'])->middleware('throttle:60,1,apppay');

    Route::get('addresses', [AddressesController::class, 'index'])->middleware('throttle:60,1,appaddrlist');
    Route::post('addresses', [AddressesController::class, 'store'])->middleware('throttle:20,1,appaddrsave');
    Route::post('addresses/{id}', [AddressesController::class, 'update'])->where('id', '[0-9]+')->middleware('throttle:20,1,appaddrsave');
    Route::post('addresses/{id}/delete', [AddressesController::class, 'destroy'])->where('id', '[0-9]+')->middleware('throttle:20,1,appaddrdel');

    Route::get('coupons', [CouponsController::class, 'index'])->middleware('throttle:30,1,appcoupons');
    Route::get('checkout/slots', [CheckoutController::class, 'slots'])->middleware('throttle:60,1,appslots');
    Route::post('checkout/quote', [CheckoutController::class, 'quote'])->middleware('throttle:30,1,appquote');
    Route::post('orders', [CheckoutController::class, 'store'])->middleware('throttle:6,1,apporder');

    Route::post('payments/razorpay/create', [PaymentsController::class, 'create'])->middleware('throttle:10,1,apppaycreate');
    Route::post('payments/razorpay/confirm', [PaymentsController::class, 'confirm'])->middleware('throttle:20,1,apppayconfirm');
    Route::get('payments/razorpay/status', [PaymentsController::class, 'status'])->middleware('throttle:30,1,apppaystatus');
});
