<?php

use App\Http\Controllers\Api\BuyerApp\ContentController;
use App\Http\Controllers\Api\BuyerApp\DeviceController;
use App\Http\Controllers\Api\BuyerApp\OrderTrackingController;
use App\Http\Controllers\Api\BuyerApp\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer app API, slice 2 - loaded by BuyerAppShopServiceProvider under /api/app
|--------------------------------------------------------------------------
| Public:  content pages (about, terms, privacy, refund, shipping, faq), cancel reasons.
| Signed in (Authorization: Bearer <token>): wishlist, order tracking, order cancel, device token, contact us.
| Banners, catalog, cart, address and orders stay on the existing /api/* routes.
*/

Route::middleware('throttle:60,1')->group(function () {
    Route::get('content/pages', [ContentController::class, 'pages']);
    Route::get('content/pages/{type}', [ContentController::class, 'page'])->where('type', '[a-z_-]{2,30}');
    Route::get('content/cancel-reasons', [ContentController::class, 'cancelReasons']);
});

Route::middleware('buyer.api')->group(function () {
    Route::get('wishlist', [WishlistController::class, 'index']);
    Route::post('wishlist/toggle', [WishlistController::class, 'toggle']);

    Route::get('orders/{orderId}/tracking', [OrderTrackingController::class, 'show'])
        ->where('orderId', '[A-Za-z0-9_-]{3,40}');
    Route::post('orders/{orderId}/cancel', [OrderTrackingController::class, 'cancel'])
        ->where('orderId', '[A-Za-z0-9_-]{3,40}')
        ->middleware('throttle:10,1');

    Route::post('device-token', [DeviceController::class, 'store']);
    Route::post('device-token/clear', [DeviceController::class, 'clear']);

    Route::post('contact', [ContentController::class, 'contact'])->middleware('throttle:5,60');
});
