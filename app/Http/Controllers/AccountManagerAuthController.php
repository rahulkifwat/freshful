<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\AccountManager;

class AccountManagerAuthController extends Controller
{
    public function login()
    {
        return view('account_manager.login');
    }

    public function loginSubmit(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = AccountManager::where('email', $request->email)->first();

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
                Auth::guard('account_managers')->login($user);
                return redirect()->route('account_manager.dashboard');
            }
        }

        return redirect()->back()
            ->with('error', 'Invalid email or password')
            ->withInput(['email' => $request->email]);
    }

    public function logout()
    {
        Auth::guard('account_managers')->logout();
        return redirect()->route('account_manager.login');
    }
}
