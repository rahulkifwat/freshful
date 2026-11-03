<?php

namespace App\Services\BuyerApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends the SMS through the same gateway settings the existing API already uses
 * (config/services.php -> bhashsms). No credentials live in this file.
 * The gateway answers "S.<message id>" when it accepts a message.
 */
class GatewaySmsSender implements SmsSender
{
    public function send(string $phone, string $text): bool
    {
        $config = config('services.bhashsms');

        if (! is_array($config) || empty($config['url'])) {
            Log::error('buyer app OTP: services.bhashsms is not configured');

            return false;
        }

        try {
            $response = Http::asForm()->connectTimeout(5)->timeout(12)->post($config['url'], [
                'user'     => $config['user'] ?? '',
                'pass'     => $config['pass'] ?? '',
                'sender'   => $config['sender'] ?? '',
                'phone'    => $phone,
                'text'     => $text,
                'priority' => 'ndnd',
                'stype'    => 'normal',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        $body = trim($response->body());
        $accepted = $response->successful() && (str_starts_with($body, 'S.') || str_starts_with($body, 'S '));

        if (! $accepted) {
            // Never log the code; only the last 4 digits of the number and the gateway's answer.
            Log::warning('buyer app OTP SMS not accepted', ['phone_tail' => substr($phone, -4), 'http' => $response->status(), 'response' => substr($body, 0, 120)]);
        }

        return $accepted;
    }
}
