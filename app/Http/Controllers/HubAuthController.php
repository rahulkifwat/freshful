<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\HubUser;

class HubAuthController extends Controller
{
    public function login()
    {
        return view('hub.login');
    }

    public function loginSubmit(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = HubUser::where('email', $request->email)->first();

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
                Auth::guard('hub_users')->login($user);
                return redirect()->route('hub.dashboard');
            }
        }

        return redirect()->back()
            ->with('error', 'Invalid email or password')
            ->withInput(['email' => $request->email]);
    }

    public function logout()
    {
        Auth::guard('hub_users')->logout();
        return redirect()->route('hub.login');
    }
}
