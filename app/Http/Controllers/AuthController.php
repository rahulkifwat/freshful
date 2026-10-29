<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Buyer;
use App\Models\BuyersAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    public function userLogin(Request $request){
        $phone = $request->phone;

        if ($request->filled('otp')) {
            // Security fix (21 Oct 2026): the OTP only works for the phone it was sent to,
            // expires after 5 minutes and allows at most 5 tries.
            $sentOtp   = (string) session('otp', '');
            $sentPhone = $this->phoneKey(session('phone', ''));
            $expiresAt = (int) session('otp_expires_at', 0);
            $tries     = (int) session('otp_tries', 0) + 1;
            session(['otp_tries' => $tries]);

            if ($sentOtp === '' || $sentPhone === '' || $sentPhone !== $this->phoneKey($phone)) {
                return redirect()->back()->with('error', 'Please request an OTP for this mobile number first.')->with('openLoginform', true);
            }
            if ($expiresAt === 0 || time() > $expiresAt) {
                session()->forget(['otp', 'otp_tries', 'otp_expires_at']);
                return redirect()->back()->with('error', 'This OTP has expired. Please request a new one.')->with('openLoginform', true);
            }
            if ($tries > 5) {
                session()->forget(['otp', 'otp_tries', 'otp_expires_at']);
                return redirect()->back()->with('error', 'Too many wrong attempts. Please request a new OTP.')->with('openLoginform', true);
            }
            if (! hash_equals($sentOtp, trim((string) $request->otp))) {
                return redirect()->back()->withInput(['phone' => $phone])->with('error', 'That code does not match. Check it and try again.')->with('otp_step', true)->with('openLoginform', true);
            }

            // OTP is correct — find or create the buyer, then ask incomplete profiles
            // to finish their contact and delivery details before shopping.
            $user = Buyer::where('phone', $phone)->first();
            if (!$user) {
                $user = Buyer::create([
                    'phone'        => $phone,
                    'status'       => 'active',
                    'phone_verify' => 1,
                    'name'         => '',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
                app(\App\Services\BuyerReferralService::class)->attachNewBuyer((int) $user->id, session('freshful_referral_code'));
                session()->forget('freshful_referral_code');
            } else {
                if (Schema::hasColumn('buyers', 'phone_verify')) {
                    $user->phone_verify = 1;
                    $user->save();
                }
                session()->forget('freshful_referral_code');
            }
            app(\App\Services\BuyerReferralService::class)->ensureCode((int) $user->id);

            $needsProfile = trim((string) $user->name) === '' || trim((string) ($user->email ?? '')) === ''
                || !DB::table('buyer_address')->where('buyer_id', $user->id)->exists();

            $guestSessionId = session()->getId();
            session()->forget(['otp', 'otp_tries', 'otp_expires_at']);
            Auth::guard('buyers')->login($user);
            $this->mergeGuestCart($guestSessionId, $user->id);
            $request->session()->regenerate();
            return redirect()->route('home')
                ->with('complete_profile', $needsProfile)
                ->with('success', $needsProfile ? 'Mobile number verified. Please complete your account and delivery address.' : 'Login successful');

        } elseif ($request->filled('password')) {
            // Security fix (21 Oct 2026): limit password guessing
            if ($this->tooMany('freshful-pw-login:' . $this->phoneKey($phone) . '|' . $request->ip(), 8, 900)) {
                return redirect()->back()->with('error', 'Too many login attempts. Please try again in 15 minutes.')->with('openLoginform', true);
            }
            $user = Buyer::where('phone', $phone)->first();
            if ($user) {
                $stored   = (string) $user->password;
                $isBcrypt = (bool) preg_match('/^\$2[aby]\$/', $stored);

                $passwordOk = $isBcrypt
                    ? Hash::check($request->password, $stored)
                    : hash_equals($stored, (string) $request->password);

                if ($passwordOk) {
                    app(\App\Services\BuyerReferralService::class)->ensureCode((int) $user->id);
                    if (!$isBcrypt) {
                        $user->password = Hash::make($request->password);
                        $user->save();
                    }
                    $guestSessionId = session()->getId();
                    Auth::guard('buyers')->login($user);
                    $this->mergeGuestCart($guestSessionId, $user->id);
                    $request->session()->regenerate();
                    return redirect()->route('home')->with('success', 'Login successful');
                }
            }
            return redirect()->back()->with('error', 'Invalid phone or password')->with('openLoginform', true);
        }

        return redirect()->back()->with('error', 'Something went wrong')->with('openLoginform', true);
    }

    public function sendOtp(Request $request)
    {
        $phone = $request->phone;

        if (!$phone || strlen($phone) < 10) {
            return response()->json(['status' => 'error', 'message' => 'Please enter a valid 10-digit mobile number']);
        }

        // Security fix (21 Oct 2026): limit OTP requests (10 per IP, 3 per phone, per 10 minutes)
        if ($this->tooMany('freshful-otp-ip:' . $request->ip(), 10, 600)
            || $this->tooMany('freshful-otp-phone:' . $this->phoneKey($phone), 3, 600)) {
            return response()->json(['status' => 'error', 'message' => 'Too many OTP requests. Please wait 10 minutes and try again.']);
        }

        $otp = random_int(100000, 999999);
        $message_body = "Dear Customer, Your One Time Password for registration is $otp, use this code to validate your login. Pls Do Not Share this OTP with anyone.Freshful";

        // Store OTP in session before the SMS attempt so login works even if SMS call throws
        session(['otp' => (string) $otp, 'phone' => $phone, 'otp_expires_at' => time() + 300, 'otp_tries' => 0]);

        try {
            // Legacy PHP sends via cURL POST — not GET with query params
            $res  = Http::asForm()->connectTimeout(5)->timeout(12)->post('http://bhashsms.com/api/sendmsg.php', [
                'user'     => 'Fastic',
                'pass'     => '123456',
                'sender'   => 'Frfull',
                'phone'    => $phone,
                'text'     => $message_body,
                'priority' => 'ndnd',
                'stype'    => 'normal',
            ]);
            $body = trim($res->body());

            // bhashsms returns "S.<msgid>" on success, "E <code> …" on failure
            if (str_starts_with($body, 'S.') || str_starts_with($body, 'S ')) {
                return response()->json(['status' => 'success', 'message' => 'OTP sent to your mobile number']);
            }

            \Log::warning('bhashsms OTP failed', ['phone' => $phone, 'response' => $body]);
            return response()->json(['status' => 'error', 'message' => 'We could not send the code right now. Please try again shortly.']);

        } catch (\Exception $e) {
            \Log::error('bhashsms exception', ['phone' => $phone, 'error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'SMS gateway error. Please try again.']);
        }
    }

    /** Complete the profile and save the first delivery address after OTP verification. */
    public function completeRegistration(Request $request)
    {
        $buyer = Auth::guard('buyers')->user();
        if (!$buyer) {
            return redirect()->route('home')->with('error', 'Please verify your mobile number first.')->with('openLoginform', true);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:buyers,email,' . $buyer->id,
            'gender'      => 'nullable|in:Male,Female',
            'address_name'=> 'required|string|max:150',
            'locality'    => 'required|string|max:255',
            'street_name' => 'required|string|max:500',
            'landmark'    => 'nullable|string|max:255',
            'city'        => 'required|string|max:150',
            'type'        => 'required|in:Home,Work,Other',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput()->with('complete_profile', true);
        }
        $data = $validator->validated();

        DB::transaction(function () use ($buyer, $data) {
            $buyer->name = trim($data['name']);
            $buyer->email = trim($data['email']);
            if (isset($data['gender'])) $buyer->gender = $data['gender'];
            if (Schema::hasColumn('buyers', 'phone_verify')) $buyer->phone_verify = 1;
            $buyer->save();

            $address = [
                'buyer_id'   => $buyer->id,
                'name'       => trim($data['address_name']),
                'phone'      => $buyer->phone,
                'locality'   => trim($data['locality']),
                'street_name'=> trim($data['street_name']),
                'landmark'   => trim((string) ($data['landmark'] ?? '')),
                'city'       => trim($data['city']),
                'type'       => $this->fitBuyerAddressType($data['type']),
            ];
            if (Schema::hasColumn('buyer_address', 'created_at')) $address['created_at'] = now();
            if (Schema::hasColumn('buyer_address', 'updated_at')) $address['updated_at'] = now();
            BuyersAddress::create($address);
        });

        return redirect()->route('home')->with('success', 'Your Freshful account and delivery address are ready.');
    }

    public function logout(){
        Auth::guard('buyers')->logout();
        return redirect()->route('home');
    }

    public function AdminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $admin = Admin::where('email', $request->email)->first();
        if (! $admin) {
            return back()->with('error', 'Invalid email or password')->withInput();
        }

        $stored   = (string) $admin->password;
        $isBcrypt = (bool) preg_match('/^\$2[aby]\$/', $stored);

        // Branch on hash format — Laravel's bcrypt hasher throws on non-bcrypt
        // input, so legacy plaintext rows must use a direct string compare.
        $passwordOk = $isBcrypt
            ? Hash::check($request->password, $stored)
            : hash_equals($stored, (string) $request->password);

        if (! $passwordOk) {
            return back()->with('error', 'Invalid email or password')->withInput();
        }

        // Migrate-on-login: upgrade legacy plaintext to bcrypt in place.
        if (! $isBcrypt) {
            $admin->password = Hash::make($request->password);
            $admin->save();
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Admin login successful');
    }

    public function adminLogout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    /** Security fix (21 Oct 2026): last 10 digits of a phone number, for comparing and limiting. */
    private function phoneKey($phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    /** Match the address type to the database enum when one is configured. */
    private function fitBuyerAddressType(string $type): string
    {
        try {
            $column = DB::selectOne("SELECT DATA_TYPE AS dt, COLUMN_TYPE AS ct, CHARACTER_MAXIMUM_LENGTH AS len FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'buyer_address' AND column_name = 'type'");
            if (!$column) return $type;
            if (strtolower((string) $column->dt) === 'enum' && preg_match('/^enum\\((.*)\\)$/i', (string) $column->ct, $matches)) {
                preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $matches[1], $values);
                $allowed = array_map(fn ($value) => stripcslashes($value), $values[1] ?? []);
                foreach ($allowed as $value) if (strcasecmp($value, $type) === 0) return $value;
                $aliases = ['home' => ['house'], 'work' => ['office'], 'other' => ['others']];
                foreach ($aliases[strtolower($type)] ?? [] as $alias) foreach ($allowed as $value) if (strcasecmp($value, $alias) === 0) return $value;
                foreach ($allowed as $value) if (in_array(strtolower($value), ['other', 'others'], true)) return $value;
                return $allowed[0] ?? $type;
            }
            return !empty($column->len) ? mb_substr($type, 0, (int) $column->len) : $type;
        } catch (\Throwable $e) {
            return $type;
        }
    }

    /** Security fix (21 Oct 2026): true when the limit is reached. If the cache fails, never blocks a real customer. */
    private function tooMany(string $key, int $max, int $seconds): bool
    {
        try {
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, $max)) {
                return true;
            }
            \Illuminate\Support\Facades\RateLimiter::hit($key, $seconds);
        } catch (\Throwable $e) {
            \Log::warning('Freshful rate limiter unavailable', ['error' => $e->getMessage()]);
        }
        return false;
    }

    private function mergeGuestCart(string $guestSessionId, int $buyerId): void
    {
        $guestItems = DB::table('cart')->where('session_id', $guestSessionId)->get();

        foreach ($guestItems as $item) {
            $existing = DB::table('cart')
                ->where('buyer_id', $buyerId)
                ->where('product_id', $item->product_id)
                ->first();

            if ($existing) {
                DB::table('cart')
                    ->where('id', $existing->id)
                    ->update(['quantity' => $existing->quantity + $item->quantity]);
                DB::table('cart')->where('id', $item->id)->delete();
            } else {
                DB::table('cart')
                    ->where('id', $item->id)
                    ->update(['buyer_id' => $buyerId]);
            }
        }
    }
}
