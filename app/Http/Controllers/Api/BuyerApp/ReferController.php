<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Services\BuyerReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * GET refer: the customer's invite code (made on first use, exactly as the website does) and how the offer works.
 * The reward figures mirror BuyerReferralService / the Refer & Earn page: Rs 150 to the referrer's wallet when the friend's
 * first non-cancelled order of Rs 400 or more is delivered. Nothing about the friends is shown except that they joined.
 */
class ReferController extends AppController
{
    public const REWARD = 150;
    public const MIN_ORDER = 400;

    public function show(Request $request): JsonResponse
    {
        $buyerId = $this->ownBuyerId($request);
        $code = (new BuyerReferralService)->ensureCode($buyerId);

        $invited = 0;
        $rewarded = 0;
        $earned = 0.0;
        foreach (DB::table('buyer_referrals')->where('referrer_buyer_id', $buyerId)->get(['status', 'reward_amount']) as $r) {
            $invited++;
            if ((string) $r->status === 'rewarded') {
                $rewarded++;
                $earned += (float) $r->reward_amount;
            }
        }

        return $this->ok([
            'code' => $code, 'reward' => self::REWARD, 'min_order' => self::MIN_ORDER,
            'invited' => $invited, 'rewarded' => $rewarded, 'earned' => round($earned, 2),
            'share_text' => 'Fresh meat, seafood and vegetables delivered to your door. Join Freshful with my invite code ' . $code . ' when you sign up. https://freshful.in',
        ]);
    }
}
