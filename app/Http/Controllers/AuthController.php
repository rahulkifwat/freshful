<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Buyer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function userLogin(Request $request){
        $phone = $request->phone;
        // dd($request->all());
        if($request->filled('otp')){
            if($request->otp == session('otp')){
                $user = Buyer::where('phone', $phone)->first();
                if($user){
                    Auth::guard('buyers')->login($user);
                    return redirect()->route('home')->with('success', 'Login successful');
                }
            }elseif($request->otp != session('otp')){
                return redirect()->back()->with('error', 'Invalid OTP')->with('openLoginform', true);
            }else{
                return redirect()->back()->with('error', 'Something went wrong')->with('openLoginform', true);
            }
        }elseif($request->filled('password')){
            $user = Buyer::where('phone', $phone)->first();
            if ($user) {
                $stored   = (string) $user->password;
                $isBcrypt = (bool) preg_match('/^\$2[aby]\$/', $stored);

                $passwordOk = $isBcrypt
                    ? Hash::check($request->password, $stored)
                    : hash_equals($stored, (string) $request->password);

                if ($passwordOk) {
                    if (! $isBcrypt) {
                        $user->password = Hash::make($request->password);
                        $user->save();
                    }
                    Auth::guard('buyers')->login($user);
                    return redirect()->route('home')->with('success', 'Login successful');
                }
            }
            return redirect()->back()->with('error', 'Invalid phone or password')->with('openLoginform', true);
        }
        return redirect()->back()->with('error', 'Something went wrong')->with('openLoginform', true);

    }

    public function sendOtp(Request $request){
        $phone = $request->phone;

        $user = Buyer::where('phone', $phone)->first();
        if(!$user){
            return response()->json(['message' => 'User not found'], 404);
        }
        $otp = rand(100000, 999999);
        $message_body = "Dear Customer, Your One Time Password for registration is $otp, use this code to validate your login. Pls Do Not Share this OTP with anyone.Freshful";
        $url = "http://bhashsms.com/api/sendmsg.php?" . http_build_query([
                'user'     => 'Fastic',
                'pass'     => '123456',
                'sender'   => 'Frfull',
                'phone'    => $phone,
                'text'     => $message_body,
                'priority' => 'ndnd',
                'stype'    => 'normal'
            ]);
            // $res = Http::get($url);
            try {
                $res = Http::get($url);

                $body = $res->body();
                if (str_contains(trim($body), 'S.')) {
                    session(['otp' => $otp,
                            'phone' => $phone]);

                    return response()->json(['message' => 'OTP sent successfully'], 200);
                } else {
                    return response()->json(['message' => 'OTP not sent provider problem'], 500);
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'OTP sending failed : ' . $e->getMessage()], 500);
            }

        return response()->json(['message' => 'OTP sent successfully'], 200);
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
}
