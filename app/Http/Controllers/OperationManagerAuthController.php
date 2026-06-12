<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class OperationManagerAuthController extends Controller
{
    private function guard() { return Auth::guard('operation_managers'); }

    public function login()
    {
        return view('operation_manager.login');
    }

    public function loginSubmit(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $manager = \App\Models\OperationManager::where('email', $request->email)->first();

        if (!$manager) {
            return back()->with('error', 'Invalid email address.')->withInput();
        }

        $stored = $manager->password;

        // Migrate plaintext → bcrypt on first login
        if (preg_match('/^\$2[aby]\$/', $stored)) {
            $valid = Hash::check($request->password, $stored);
        } else {
            $valid = hash_equals($stored, $request->password);
            if ($valid) {
                $manager->password = Hash::make($request->password);
                $manager->save();
            }
        }

        if (!$valid) {
            return back()->with('error', 'Invalid password.')->withInput();
        }

        if (($manager->status ?? 'active') !== 'active') {
            return back()->with('error', 'Your account is inactive.')->withInput();
        }

        $this->guard()->login($manager);
        return redirect()->route('operation_manager.dashboard');
    }

    public function logout()
    {
        $this->guard()->logout();
        return redirect()->route('operation_manager.login');
    }
}
