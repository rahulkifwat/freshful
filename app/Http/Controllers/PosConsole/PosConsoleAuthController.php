<?php

namespace App\Http\Controllers\PosConsole;

use App\Http\Controllers\Controller;
use App\Models\HubUser;
use App\Models\PosUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Freshful POS console - login for POS cashiers (pos_user table) and hub managers (hub_user table).
 * Each person only ever sees their own hub. 8 wrong tries per 15 minutes per email + connection.
 */
class PosConsoleAuthController extends Controller
{
    public function showLogin()
    {
        return view('pos_console.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|string|max:191',
            'password' => 'required|string|max:191',
        ]);
        $login = mb_strtolower(trim($data['email']));
        $key = 'freshful-posc-login:' . sha1($login) . '|' . $request->ip();

        try {
            if (RateLimiter::tooManyAttempts($key, 8)) {
                $mins = (int) ceil(RateLimiter::availableIn($key) / 60);
                return back()->withInput(['email' => $data['email']])->with('error', 'Too many attempts. Try again in ' . max(1, $mins) . ' minute(s).');
            }
        } catch (\Throwable $e) {
            // limiter unavailable - continue
        }

        foreach (['pos_users' => PosUser::class, 'hub_users' => HubUser::class] as $guard => $model) {
            try {
                $user = $model::query()->where(function ($q) use ($login) {
                    $q->whereRaw('LOWER(email) = ?', [$login]);
                    if (preg_match('/^\+?\d{10,13}$/', $login)) {
                        $q->orWhere('phone', substr(preg_replace('/\D/', '', $login), -10));
                    }
                })->first();
            } catch (\Throwable $e) {
                $user = null;   // table missing on this install
            }
            if (!$user) continue;

            $stored = (string) $user->password;
            $isHash = (bool) preg_match('/^\$2[aby]\$/', $stored);
            $ok = $isHash ? Hash::check($data['password'], $stored) : ($stored !== '' && hash_equals($stored, $data['password']));
            if (!$ok) continue;

            if (strtolower((string) ($user->status ?? 'active')) !== 'active') {
                return back()->withInput(['email' => $data['email']])->with('error', 'This account is switched off. Ask the admin to switch it on.');
            }
            if ((int) ($user->hub_id ?? 0) <= 0) {
                return back()->withInput(['email' => $data['email']])->with('error', 'This account is not linked to a hub yet. Ask the admin to set its hub.');
            }
            if (!$isHash) {
                $user->password = Hash::make($data['password']);
                $user->save();
            }

            foreach (['pos_users', 'hub_users'] as $g) {
                if ($g !== $guard) {
                    try { Auth::guard($g)->logout(); } catch (\Throwable $e) {}
                }
            }
            Auth::guard($guard)->login($user);
            $request->session()->regenerate();
            try { RateLimiter::clear($key); } catch (\Throwable $e) {}
            return redirect()->route('posc.board');
        }

        try { RateLimiter::hit($key, 900); } catch (\Throwable $e) {}
        return back()->withInput(['email' => $data['email']])->with('error', 'Email or password is not correct.');
    }

    public function logout(Request $request)
    {
        foreach (['pos_users', 'hub_users'] as $g) {
            try { Auth::guard($g)->logout(); } catch (\Throwable $e) {}
        }
        $request->session()->regenerateToken();
        return redirect()->route('posc.login')->with('success', 'You have logged out.');
    }
}
