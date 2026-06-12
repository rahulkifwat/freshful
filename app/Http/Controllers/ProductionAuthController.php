<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProductionAuthController extends Controller
{
    private function guard() { return Auth::guard('productions'); }

    public function login()
    {
        return view('production.login');
    }

    public function loginSubmit(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = \App\Models\Production::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'Invalid email address.')->withInput();
        }

        $stored = $user->password;

        // Migrate plaintext → bcrypt on first login
        if (preg_match('/^\$2[aby]\$/', $stored)) {
            $valid = Hash::check($request->password, $stored);
        } else {
            $valid = hash_equals($stored, $request->password);
            if ($valid) {
                $user->password = Hash::make($request->password);
                $user->save();
            }
        }

        if (!$valid) {
            return back()->with('error', 'Invalid password.')->withInput();
        }

        if (($user->status ?? 'active') !== 'active') {
            return back()->with('error', 'Your account is inactive.')->withInput();
        }

        $this->guard()->login($user);
        return redirect()->route('production.dashboard');
    }

    public function logout()
    {
        $this->guard()->logout();
        return redirect()->route('production.login');
    }
}
