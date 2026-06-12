<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\PlanningManager;

class PlanningManagerAuthController extends Controller
{
    public function login()
    {
        return view('planning_manager.login');
    }

    public function loginSubmit(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $manager = PlanningManager::where('email', $request->email)->first();

        if (!$manager) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        $stored = $manager->password;

        // Migrate plain-text passwords to bcrypt on first login
        if (!preg_match('/^\$2[aby]\$/', $stored)) {
            if (!hash_equals($stored, $request->password)) {
                return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
            }
            $manager->password = Hash::make($request->password);
            $manager->save();
            Auth::guard('planning_managers')->login($manager);
            return redirect()->route('planning_manager.dashboard');
        }

        if (!Auth::guard('planning_managers')->attempt(['email' => $request->email, 'password' => $request->password])) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        return redirect()->route('planning_manager.dashboard');
    }

    public function logout()
    {
        Auth::guard('planning_managers')->logout();
        return redirect()->route('planning_manager.login');
    }
}
