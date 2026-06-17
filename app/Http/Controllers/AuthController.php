<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Buyer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function userLogin(Request $request){
        $phone = $request->phone;

        if ($request->filled('otp')) {
            if ($request->otp != session('otp')) {
                return redirect()->back()->with('error', 'Invalid OTP. Please try again.')->with('openLoginform', true);
            }

            // OTP is correct — find or auto-register the buyer (mirrors PHP behavior)
            $user = Buyer::where('phone', $phone)->first();
            if (!$user) {
                $user = Buyer::create([
                    'phone'        => $phone,
                    'status'       => 'active',
                    'phone_verify' => 0,
                    'name'         => '',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            $guestSessionId = session()->getId();
            session()->forget('otp');
            Auth::guard('buyers')->login($user);
            $this->mergeGuestCart($guestSessionId, $user->id);
            return redirect()->route('home')->with('success', 'Login successful');

        } elseif ($request->filled('password')) {
            $user = Buyer::where('phone', $phone)->first();
            if ($user) {
                $stored   = (string) $user->password;
                $isBcrypt = (bool) preg_match('/^\$2[aby]\$/', $stored);

                $passwordOk = $isBcrypt
                    ? Hash::check($request->password, $stored)
                    : hash_equals($stored, (string) $request->password);

                if ($passwordOk) {
                    if (!$isBcrypt) {
                        $user->password = Hash::make($request->password);
                        $user->save();
                    }
                    $guestSessionId = session()->getId();
                    Auth::guard('buyers')->login($user);
                    $this->mergeGuestCart($guestSessionId, $user->id);
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

        $otp = rand(100000, 999999);
        $message_body = "Dear Customer, Your One Time Password for registration is $otp, use this code to validate your login. Pls Do Not Share this OTP with anyone.Freshful";

        // Store OTP in session before the SMS attempt so login works even if SMS call throws
        session(['otp' => $otp, 'phone' => $phone]);

        try {
            // Legacy PHP sends via cURL POST — not GET with query params
            $res  = Http::asForm()->post('http://bhashsms.com/api/sendmsg.php', [
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
            return response()->json(['status' => 'error', 'message' => 'Failed to send OTP: ' . $body]);

        } catch (\Exception $e) {
            \Log::error('bhashsms exception', ['phone' => $phone, 'error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'SMS gateway error. Please try again.']);
        }
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
                    ->update(['buyer_id' => $buyerId, 'session_id' => null]);
            }
        }
    }
}
