<?php

use App\Http\Controllers\Api\BuyerApp\AddressesController;
use App\Http\Controllers\Api\BuyerApp\AppCartController;
use App\Http\Controllers\Api\BuyerApp\CatalogController;
use App\Http\Controllers\Api\BuyerApp\CheckoutController;
use App\Http\Controllers\Api\BuyerApp\CouponsController;
use App\Http\Controllers\Api\BuyerApp\OrderExtrasController;
use App\Http\Controllers\Api\BuyerApp\OrderHistoryController;
use App\Http\Controllers\Api\BuyerApp\PaymentMethodsController;
use App\Http\Controllers\Api\BuyerApp\OrderWeightsController;
use App\Http\Controllers\Api\BuyerApp\PaymentsController;
use App\Http\Controllers\Api\BuyerApp\ReferController;
use App\Http\Controllers\Api\BuyerApp\ServiceAreaController;
use App\Http\Controllers\Api\BuyerApp\WalletController;
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
|   GET  catalog/home|categories|categories/{id}/items|items/{id}|search|sections/{key}   what is on sale, shaped for the app (no cost price)
|   GET  cart / POST cart/set / POST cart/clear     the customer's cart with server prices
|   GET  coupons                     coupons with eligibility and savings for the current cart
|   GET  checkout/slots              delivery times (Express and slots)
|   POST checkout/quote              full price breakdown from the server cart, nothing is saved
|   GET  orders / orders/{id}        the customer's order list and one order's items and bill (status: orders/{id}/tracking)
|   POST orders/{id}/reorder         put the available items of an earlier order into the cart
|   GET  wallet                      balance and statement
|   POST orders/{id}/invoice         link to the website's invoice page (valid 7 days)
|   GET|POST orders/{id}/ratings     rate the items of a delivered order
|   GET  orders/{id}/weights         exact weights found when the store packed the order
|   GET  refer                       invite code and how Refer and Earn works
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

    Route::get('catalog/home', [CatalogController::class, 'home'])->middleware('throttle:120,1,appcatalog');
    Route::get('catalog/categories', [CatalogController::class, 'categories'])->middleware('throttle:120,1,appcatalog');
    Route::get('catalog/categories/{id}/items', [CatalogController::class, 'categoryItems'])->where('id', '[0-9]+')->middleware('throttle:120,1,appcatalog');
    Route::get('catalog/items/{id}', [CatalogController::class, 'item'])->where('id', '[0-9]+')->middleware('throttle:120,1,appcatalog');
    Route::get('catalog/search', [CatalogController::class, 'search'])->middleware('throttle:120,1,appcatalog');
    Route::get('catalog/sections/{key}', [CatalogController::class, 'section'])->where('key', '[a-z0-9-]+')->middleware('throttle:120,1,appcatalog');
    Route::get('cart', [AppCartController::class, 'show'])->middleware('throttle:120,1,appcart');
    Route::post('cart/set', [AppCartController::class, 'set'])->middleware('throttle:120,1,appcartset');
    Route::post('cart/clear', [AppCartController::class, 'clear'])->middleware('throttle:20,1,appcartclear');

    Route::get('coupons', [CouponsController::class, 'index'])->middleware('throttle:30,1,appcoupons');
    Route::get('checkout/slots', [CheckoutController::class, 'slots'])->middleware('throttle:60,1,appslots');
    Route::post('checkout/quote', [CheckoutController::class, 'quote'])->middleware('throttle:30,1,appquote');
    Route::post('orders', [CheckoutController::class, 'store'])->middleware('throttle:6,1,apporder');
    Route::get('orders', [OrderHistoryController::class, 'index'])->middleware('throttle:60,1,apporderlist');
    Route::get('orders/{orderId}', [OrderHistoryController::class, 'show'])->where('orderId', '[A-Za-z0-9_-]{3,30}')->middleware('throttle:60,1,apporderone');
    Route::post('orders/{orderId}/reorder', [OrderHistoryController::class, 'reorder'])->where('orderId', '[A-Za-z0-9_-]{3,30}')->middleware('throttle:20,1,appreorder');
    Route::get('wallet', [WalletController::class, 'show'])->middleware('throttle:60,1,appwallet');
    Route::post('orders/{orderId}/invoice', [OrderExtrasController::class, 'invoice'])->where('orderId', '[A-Za-z0-9_-]{3,30}')->middleware('throttle:20,1,appinvoice');
    Route::get('orders/{orderId}/ratings', [OrderExtrasController::class, 'ratings'])->where('orderId', '[A-Za-z0-9_-]{3,30}')->middleware('throttle:60,1,appratings');
    Route::post('orders/{orderId}/ratings', [OrderExtrasController::class, 'rate'])->where('orderId', '[A-Za-z0-9_-]{3,30}')->middleware('throttle:30,1,apprate');
    Route::get('orders/{orderId}/weights', [OrderWeightsController::class, 'show'])->where('orderId', '[A-Za-z0-9_-]{3,30}')->middleware('throttle:60,1,appweights');
    Route::get('refer', [ReferController::class, 'show'])->middleware('throttle:30,1,apprefer');

    Route::post('payments/razorpay/create', [PaymentsController::class, 'create'])->middleware('throttle:10,1,apppaycreate');
    Route::post('payments/razorpay/confirm', [PaymentsController::class, 'confirm'])->middleware('throttle:20,1,apppayconfirm');
    Route::get('payments/razorpay/status', [PaymentsController::class, 'status'])->middleware('throttle:30,1,apppaystatus');
});
