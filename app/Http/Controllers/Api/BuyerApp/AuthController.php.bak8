<?php

namespace App\Http\Controllers\Api\BuyerApp;

use App\Models\Buyer;
use App\Services\BuyerApp\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Sign-in for the customer app. One flow for new and existing customers:
 *   POST /api/app/auth/otp/request  -> SMS code
 *   POST /api/app/auth/otp/verify   -> account found or created, Bearer token returned
 *   POST /api/app/auth/password     -> for customers who set a password
 */
class AuthController extends AppController
{
    private const MESSAGES = [
        'too_soon'    => 'Please wait a minute before asking for another code.',
        'too_many'    => 'Too many codes requested. Please try again in an hour.',
        'sms_failed'  => 'We could not send the code right now. Please try again shortly.',
        'otp_format'  => 'Enter the 6 digit code from the SMS.',
        'no_otp'      => 'This code is no longer valid. Please request a new one.',
        'expired'     => 'This code has expired. Please request a new one.',
        'locked'      => 'Too many wrong attempts. Please request a new code.',
        'otp_invalid' => 'That code is not correct. Please check it and try again.',
    ];

    public function requestOtp(Request $request, OtpService $otp): JsonResponse
    {
        $phone = OtpService::normalizePhone($request->input('phone'));
        if ($phone === null) {
            return $this->fail('Please enter a valid 10-digit mobile number.', 'invalid_phone');
        }

        $buyer = $this->findBuyerByPhone($phone);
        if ($buyer && (string) $buyer->status === 'disabled') {
            return $this->fail('This account is disabled. Please contact Freshful support.', 'account_disabled');
        }

        $result = $otp->issue($phone, 'login', $request->ip());
        if (! $result['ok']) {
            return $this->otpFailure($result);
        }

        return $this->ok([
            'is_new_user'  => $buyer === null,
            'expires_in'   => $result['expires_in'],
            'resend_after' => $result['resend_after'],
        ], 'OTP sent');
    }

    public function verifyOtp(Request $request, OtpService $otp): JsonResponse
    {
        $phone = OtpService::normalizePhone($request->input('phone'));
        if ($phone === null) {
            return $this->fail('Please enter a valid 10-digit mobile number.', 'invalid_phone');
        }

        $result = $otp->verify($phone, (string) $request->input('otp', ''));
        if (! $result['ok']) {
            return $this->otpFailure($result);
        }

        // The phone is proven. Find the customer or create one (never twice for the same number).
        [$row, $isNew] = Cache::lock('buyer-app-create:' . $phone, 10)->block(5, function () use ($phone, $request) {
            $existing = $this->findBuyerByPhone($phone);
            if ($existing) {
                return [$existing, false];
            }

            $buyer = Buyer::create([
                'phone'        => $phone,
                'status'       => 'active',
                'phone_verify' => 1,
                'name'         => '',
                'otp'          => '',   // the table default is a fixed code; never leave that on a new account
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $this->referral('attach', (int) $buyer->id, trim((string) $request->input('referral_code', '')) ?: null);

            return [$this->findBuyerById((int) $buyer->id), true];
        });

        if ((string) $row->status === 'disabled') {
            return $this->fail('This account is disabled. Please contact Freshful support.', 'account_disabled');
        }

        if (! $isNew) {
            DB::table('buyers')->where('id', $row->id)->update(['phone_verify' => 1, 'updated_at' => now()]);
        }
        $this->referral('ensure', (int) $row->id);

        $row = $this->findBuyerById((int) $row->id);

        return $this->ok($this->issueToken((int) $row->id) + [
            'is_new_user'   => $isNew,
            'needs_profile' => $this->needsProfile($row),
            'buyer'         => $this->buyerPayload($row),
        ], 'Login successful');
    }

    public function passwordLogin(Request $request): JsonResponse
    {
        $phone = OtpService::normalizePhone($request->input('phone'));
        $password = (string) $request->input('password', '');

        if ($phone === null || $password === '') {
            return $this->fail('Invalid phone or password.', 'invalid_credentials');
        }

        $limiterKey = 'buyer-app-pw:' . $phone . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, 8)) {
            return $this->fail('Too many attempts. Please try again in 15 minutes.', 'too_many', ['retry_after' => RateLimiter::availableIn($limiterKey)]);
        }

        $row = $this->findBuyerByPhone($phone);
        $valid = false;
        $upgradeHash = false;

        if ($row && (string) $row->status !== 'disabled') {
            $stored = (string) $row->password;
            if ($stored !== '') {
                if (preg_match('/^\$2[aby]\$/', $stored) === 1) {
                    $valid = Hash::check($password, $stored);
                } else {
                    // Old accounts may still hold a plain password: accept it once and store a hash instead.
                    $valid = hash_equals($stored, $password);
                    $upgradeHash = $valid;
                }
            }
        }

        if (! $valid) {
            RateLimiter::hit($limiterKey, 900);

            return $this->fail('Invalid phone or password.', 'invalid_credentials');
        }

        RateLimiter::clear($limiterKey);
        if ($upgradeHash) {
            DB::table('buyers')->where('id', $row->id)->update(['password' => Hash::make($password), 'updated_at' => now()]);
        }
        $this->referral('ensure', (int) $row->id);

        return $this->ok($this->issueToken((int) $row->id) + [
            'is_new_user'   => false,
            'needs_profile' => $this->needsProfile($row),
            'buyer'         => $this->buyerPayload($row),
        ], 'Login successful');
    }

    private function otpFailure(array $result): JsonResponse
    {
        $code = $result['code'] ?? 'otp_invalid';
        $extra = [];
        foreach (['retry_after', 'attempts_left'] as $key) {
            if (isset($result[$key])) {
                $extra[$key] = $result[$key];
            }
        }

        return $this->fail(self::MESSAGES[$code] ?? 'Something went wrong. Please try again.', $code, $extra);
    }

    /** Referral bookkeeping the website already does at login. Must never block a login. */
    private function referral(string $action, int $buyerId, ?string $code = null): void
    {
        try {
            $service = app(\App\Services\BuyerReferralService::class);
            if ($action === 'attach') {
                $service->attachNewBuyer($buyerId, $code);
            } else {
                $service->ensureCode($buyerId);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
