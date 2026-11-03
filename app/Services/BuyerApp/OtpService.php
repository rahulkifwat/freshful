<?php

namespace App\Services\BuyerApp;

/**
 * One-time-password rules for the Freshful customer app.
 * Login and sign-up are the same flow: verify the phone, then the account is
 * found or created.
 *
 * Plain PHP on purpose: storage (OtpStore) and SMS (SmsSender) are injected.
 *
 * Rules:
 *  - 6 digit code, valid 5 minutes, only the hash is stored
 *  - at most 5 tries per code (the 5th wrong try burns the code)
 *  - a phone can ask for a new code once per 60 seconds and 5 times per hour,
 *    however the number is written (+91, 0, spaces)
 *  - asking for a new code makes older codes useless, but only after the new
 *    SMS was really accepted by the gateway
 *  - a code can be used once
 */
class OtpService
{
    public const CODE_LENGTH = 6;
    public const TTL_SECONDS = 300;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_SECONDS = 60;
    public const MAX_PER_HOUR = 5;

    /** @var callable */
    private $clock;

    public function __construct(
        private OtpStore $store,
        private SmsSender $sms,
        private string $secret,
        ?callable $clock = null
    ) {
        if ($secret === '') {
            throw new \InvalidArgumentException('OTP secret must not be empty');
        }
        $this->clock = $clock ?? 'time';
    }

    /**
     * Indian mobile number as 10 digits, or null if it is not one.
     * Accepts 9876543210, 09876543210, 919876543210, +91 98765-43210.
     */
    public static function normalizePhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && $digits[0] === '0') {
            $digits = substr($digits, 1);
        }

        return preg_match('/^[6-9][0-9]{9}$/', $digits) === 1 ? $digits : null;
    }

    /** Same wording as the website and the old app (approved SMS template). */
    public static function message(string $code): string
    {
        return "Dear Customer, Your One Time Password for registration is $code, use this code to validate your login. Pls Do Not Share this OTP with anyone.Freshful";
    }

    /**
     * Create and send a code.
     * @return array{ok:bool, code?:string, retry_after?:int, expires_in?:int, resend_after?:int}
     */
    public function issue(string $phone, string $purpose = 'login', ?string $ip = null): array
    {
        $now = $this->now();
        $this->store->purgeOlderThan($now - 86400);

        $last = $this->store->lastIssuedAt($phone);
        if ($last !== null && ($now - $last) < self::RESEND_SECONDS) {
            return ['ok' => false, 'code' => 'too_soon', 'retry_after' => self::RESEND_SECONDS - ($now - $last)];
        }
        if ($this->store->countSince($phone, $now - 3600) >= self::MAX_PER_HOUR) {
            return ['ok' => false, 'code' => 'too_many', 'retry_after' => 3600];
        }

        $code = str_pad((string) random_int(0, (10 ** self::CODE_LENGTH) - 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);
        $id = $this->store->insert($phone, $purpose, $this->hash($phone, $code), $now + self::TTL_SECONDS, $ip, $now);

        $sent = false;
        try {
            $sent = $this->sms->send($phone, self::message($code));
        } catch (\Throwable $e) {
            $sent = false;
        }

        if (! $sent) {
            // The new code was never delivered: forget it and keep any earlier valid code.
            $this->store->delete($id);

            return ['ok' => false, 'code' => 'sms_failed'];
        }

        $this->store->consumeOpenExcept($phone, $id, $now);

        return ['ok' => true, 'expires_in' => self::TTL_SECONDS, 'resend_after' => self::RESEND_SECONDS];
    }

    /**
     * Check a code the customer typed.
     * @return array{ok:bool, code?:string, attempts_left?:int}
     */
    public function verify(string $phone, string $code, string $purpose = 'login'): array
    {
        $code = trim($code);
        if (preg_match('/^[0-9]{' . self::CODE_LENGTH . '}$/', $code) !== 1) {
            return ['ok' => false, 'code' => 'otp_format'];
        }

        $now = $this->now();
        $row = $this->store->latestOpen($phone, $purpose);
        if ($row === null) {
            return ['ok' => false, 'code' => 'no_otp'];
        }

        if ($row['expires_ts'] <= $now) {
            $this->store->consume($row['id'], $now);

            return ['ok' => false, 'code' => 'expired'];
        }

        // Count the try BEFORE comparing, so parallel guesses cannot beat the limit.
        $attempts = $this->store->incrementAttempts($row['id']);
        if ($attempts > self::MAX_ATTEMPTS) {
            $this->store->consume($row['id'], $now);

            return ['ok' => false, 'code' => 'locked'];
        }

        if (! hash_equals($row['code_hash'], $this->hash($phone, $code))) {
            if ($attempts >= self::MAX_ATTEMPTS) {
                $this->store->consume($row['id'], $now);

                return ['ok' => false, 'code' => 'locked'];
            }

            return ['ok' => false, 'code' => 'otp_invalid', 'attempts_left' => self::MAX_ATTEMPTS - $attempts];
        }

        if (! $this->store->consume($row['id'], $now)) {
            return ['ok' => false, 'code' => 'no_otp']; // used by a parallel request a moment ago
        }

        return ['ok' => true];
    }

    private function hash(string $phone, string $code): string
    {
        return hash_hmac('sha256', $phone . '|' . $code, $this->secret);
    }

    private function now(): int
    {
        return (int) ($this->clock)();
    }
}
