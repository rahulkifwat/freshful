<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Buyer;
use Illuminate\Support\Facades\Auth;
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
            // Verify password and login
            $user = Buyer::where('phone', $phone)->first();
            if($user && password_verify($request->password, $user->password)){
                Auth::guard('buyers')->login($user);
                return redirect()->route('home')->with('success', 'Login successful');
            }else{
                return redirect()->back()->with('error', 'Invalid password')->with('openLoginform', true);
            }
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

    public function AdminLogin(Request $request){
        try {
            // dd($request);
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            if (Auth::guard('admin')->attempt([
                'email'    => $request->email,
                'password' => $request->password,
            ])) {
                $request->session()->regenerate();
                return redirect()->route('admin.dashboard')
                    ->with('success', 'Admin login successful');
            }
            dd('Invalid email or password');
            
            return back()->with('error', 'Invalid email or password');
            
            } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }

    }
}
