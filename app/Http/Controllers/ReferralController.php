<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    public function landing(Request $request)
    {
        $code = strtoupper(trim((string) $request->query('code', '')));
        $referrer = $code !== '' ? Buyer::where('referral_code', $code)->first() : null;
        if ($referrer && !Auth::guard('buyers')->check() && !session()->has('freshful_referral_code')) {
            session(['freshful_referral_code' => $code]);
        }
        return view('refer_earn', [
            'referralCode' => $referrer ? $code : null,
            'referralApplied' => (bool) ($referrer && !Auth::guard('buyers')->check()),
        ]);
    }
}
