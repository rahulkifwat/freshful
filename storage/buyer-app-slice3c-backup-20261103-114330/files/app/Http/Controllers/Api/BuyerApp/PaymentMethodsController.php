<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerApp\PaymentOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Only payment methods that are really available are returned, so the app never shows a button that cannot work.
 * Cash on delivery is always there. The others need their switch in config/buyer_app_commerce.php (.env) AND the
 * backend pieces: online needs Razorpay keys on the server, wallet needs a balance.
 */
class PaymentMethodsController extends AppController
{
    public function index(Request $request): JsonResponse
    {
        return $this->ok(['methods' => PaymentOptions::available($this->ownBuyerId($request)), 'default' => 'cash']);
    }
}
