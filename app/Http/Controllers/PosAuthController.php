<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\PosUser;

class PosAuthController extends Controller
{
    public function login()
    {
        return view('pos.login');
    }

    public function loginSubmit(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = PosUser::where('email', $request->email)->where('status', 'active')->first();

        if (!$user) {
            return back()->with('error', 'Invalid credentials or account inactive.')->withInput();
        }

        $stored = $user->password;

        // Migrate plain-text passwords to bcrypt on first login
        if (!preg_match('/^\$2[aby]\$/', $stored)) {
            if (!hash_equals($stored, $request->password)) {
                return back()->with('error', 'Invalid credentials.')->withInput();
            }
            $user->password = Hash::make($request->password);
            $user->save();
            Auth::guard('pos_users')->login($user);
            return redirect()->route('pos.dashboard');
        }

        if (!Auth::guard('pos_users')->attempt(['email' => $request->email, 'password' => $request->password, 'status' => 'active'])) {
            return back()->with('error', 'Invalid credentials.')->withInput();
        }

        return redirect()->route('pos.dashboard');
    }

    public function logout()
    {
        Auth::guard('pos_users')->logout();
        return redirect()->route('pos.login');
    }
}
