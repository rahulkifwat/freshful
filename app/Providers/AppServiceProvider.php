<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureApiRateLimits();
    }

    /**
     * Brute-force / SMS-abuse limits for the API login endpoints (used via throttle:<name>).
     * Each limit is counted separately per account and per IP.
     */
    private function configureApiRateLimits(): void
    {
        $key = fn (Request $request, string $field) => strtolower(trim((string) $request->input($field)));

        // /api/login sends an SMS: stop SMS flooding of one number and from one client.
        RateLimiter::for('otp-send', fn (Request $request) => [
            Limit::perMinute(1)->by('otp-send:phone:'.$key($request, 'phone'))->response($this->tooMany(...)),
            Limit::perHour(5)->by('otp-send:phone-hour:'.$key($request, 'phone'))->response($this->tooMany(...)),
            Limit::perHour(20)->by('otp-send:ip:'.$request->ip())->response($this->tooMany(...)),
        ]);

        // /api/otp: 5 guesses per 15 minutes makes the 5-digit OTP impractical to brute force.
        RateLimiter::for('otp-verify', fn (Request $request) => [
            Limit::perMinutes(15, 5)->by('otp-verify:phone:'.$key($request, 'phone'))->response($this->tooMany(...)),
            Limit::perMinutes(15, 30)->by('otp-verify:ip:'.$request->ip())->response($this->tooMany(...)),
        ]);

        // Driver and admin email/password logins.
        RateLimiter::for('password-login', fn (Request $request) => [
            Limit::perMinutes(15, 10)->by('login:email:'.$key($request, 'email'))->response($this->tooMany(...)),
            Limit::perMinutes(15, 30)->by('login:ip:'.$request->ip())->response($this->tooMany(...)),
        ]);
    }

    /** Legacy API shape (HTTP 200, result=false) so the apps show the message instead of crashing. */
    private function tooMany(Request $request, array $headers)
    {
        $seconds = (int) ($headers['Retry-After'] ?? 60);

        return response()->json([
            'result'      => false,
            'message'     => 'Too many attempts. Please try again in '.max(1, (int) ceil($seconds / 60)).' minute(s).',
            'retry_after' => $seconds,
        ], 200, $headers);
    }
}
