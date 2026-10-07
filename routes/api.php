<?php

use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Buyer\AddressController;
use App\Http\Controllers\Api\Buyer\AuthController as BuyerAuthController;
use App\Http\Controllers\Api\Buyer\CartController;
use App\Http\Controllers\Api\Buyer\CatalogController;
use App\Http\Controllers\Api\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\Api\Buyer\ProfileController as BuyerProfileController;
use App\Http\Controllers\Api\Driver\AdminController as DriverAdminController;
use App\Http\Controllers\Api\Driver\AuthController as DriverAuthController;
use App\Http\Controllers\Api\Driver\OrderController as DriverOrderController;
use App\Http\Controllers\Api\Driver\ProfileController as DriverProfileController;
use App\Http\Controllers\Api\Driver\WalletController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes  (prefix /api, stateless — controllers in app/Http/Controllers/Api)
|--------------------------------------------------------------------------
| Ported from the legacy plain-PHP /api folder. Paths are the legacy ones
| without ".php" (api/get_profile.php -> /api/get_profile) and responses keep
| the legacy JSON shape, so the apps only need the new base URL.
|
| Buyer endpoints accepted GET or POST ($_REQUEST) and still do.
*/

$any = ['GET', 'POST'];

// ---- Buyer app (was api/*.php) ----
// Public: login (sends OTP), OTP check (returns the buyer token), shared catalog data.
Route::match($any, 'login', [BuyerAuthController::class, 'login'])->middleware('throttle:otp-send');
Route::match($any, 'otp', [BuyerAuthController::class, 'otp'])->middleware('throttle:otp-verify');

Route::match($any, 'get_banner_images', [CatalogController::class, 'banners']);
Route::match($any, 'get_app_banner_images', [CatalogController::class, 'appBanners']);
Route::match($any, 'product_tags', [CatalogController::class, 'tags']);
Route::match($any, 'category', [CatalogController::class, 'categories']);
Route::match($any, 'get_cities', [CatalogController::class, 'cities']);
Route::match($any, 'get_nearest_hub', [CatalogController::class, 'nearestHub']);
Route::match($any, 'get_products_by_tag', [CatalogController::class, 'productsByTag']); // uses the token if sent

// Authorization: Bearer <buyer token from /api/otp>. The buyer always comes from the
// token; a buyer_id/id that doesn't match it is refused.
Route::middleware('buyer.api')->group(function () use ($any) {
    Route::post('logout', [BuyerAuthController::class, 'logout']);

    Route::match($any, 'get_profile', [BuyerProfileController::class, 'show']);
    Route::match($any, 'update_profile', [BuyerProfileController::class, 'update']);

    Route::match($any, 'home_products', [CatalogController::class, 'homeProducts']);
    Route::match($any, 'get_products_by_category', [CatalogController::class, 'productsByCategory']);
    Route::match($any, 'search_products', [CatalogController::class, 'search']);

    Route::match($any, 'add_cart', [CartController::class, 'store']);
    Route::match($any, 'cart', [CartController::class, 'index']);
    Route::match($any, 'empty_cart', [CartController::class, 'destroy']);

    Route::match($any, 'add_address', [AddressController::class, 'store']);
    Route::match($any, 'get_address', [AddressController::class, 'index']);
    Route::match($any, 'update_address', [AddressController::class, 'update']);
    Route::match($any, 'delete_address', [AddressController::class, 'destroy']);

    Route::match($any, 'add_order', [BuyerOrderController::class, 'store']);
    Route::match($any, 'get_my_orders', [BuyerOrderController::class, 'index']);
    Route::match($any, 'get_order_details', [BuyerOrderController::class, 'show']);
});

// ---- Admin API auth: returns a Sanctum token for the admin.api routes below ----
Route::post('admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:password-login');
Route::post('admin/logout', [AdminAuthController::class, 'logout'])->middleware('admin.api');

// ---- Driver app (was api/driver/**/*.php) ----
Route::prefix('driver')->group(function () use ($any) {
    Route::match($any, 'auth/login', [DriverAuthController::class, 'login'])->middleware('throttle:password-login');
    Route::match($any, 'auth/register', [DriverAuthController::class, 'register']);

    // Admin token required (legacy had no auth on these at all).
    Route::middleware('admin.api')->group(function () use ($any) {
        Route::prefix('admin')->group(function () use ($any) {
            Route::match($any, 'pending-drivers', [DriverAdminController::class, 'pending']);
            Route::match($any, 'driver-verify', [DriverAdminController::class, 'verify']);
            Route::match($any, 'driver-approval', [DriverAdminController::class, 'approval']);
            Route::match($any, 'toggle-driver-status', [DriverAdminController::class, 'toggleStatus']);
        });

        Route::match($any, 'users', [DriverAdminController::class, 'users']);

        Route::match($any, 'wallet/create', [WalletController::class, 'credit']);
        Route::match($any, 'wallet/credit', [WalletController::class, 'credit']);
        Route::match($any, 'wallet/debit', [WalletController::class, 'debit']);
    });

    // Authorization: Bearer <driver JWT>
    Route::middleware('driver.jwt')->group(function () use ($any) {
        Route::match($any, 'driver/validate-token', [DriverProfileController::class, 'validateToken']);
        Route::get('driver/bank-details', [DriverProfileController::class, 'bankDetails']);
        Route::post('driver/bank-details', [DriverProfileController::class, 'saveBankDetails']);
        Route::get('driver/documents', [DriverProfileController::class, 'documents']);
        Route::post('driver/documents', [DriverProfileController::class, 'saveDocuments']);
        Route::get('driver/vehical', [DriverProfileController::class, 'vehicles']);
        Route::post('driver/vehical', [DriverProfileController::class, 'saveVehicle']);
        Route::match($any, 'driver/personal-information', [DriverProfileController::class, 'personalInformation']);
        Route::match($any, 'driver/verification', [DriverProfileController::class, 'submitVerification']);

        Route::match($any, 'orders/driver-orders', [DriverOrderController::class, 'index']);
        Route::match($any, 'orders/order-details', [DriverOrderController::class, 'show']);
        Route::match($any, 'orders/orders-by-status', [DriverOrderController::class, 'byStatus']);

        Route::get('wallet/balance', [WalletController::class, 'balance']);
        Route::get('wallet/transactions', [WalletController::class, 'transactions']);
    });
});
