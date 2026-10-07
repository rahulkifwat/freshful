<?php

namespace App\Support;

use App\Exceptions\ApiException;

/**
 * HS256 JWT for the driver app, byte-compatible with the legacy jwt_encode()/jwt_decode()
 * in functions/functions.php, so tokens issued by either side stay valid on the other.
 */
final class DriverJwt
{
    public static function encode(array $payload): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + (int) config('services.driver_jwt.ttl');

        $header = self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = self::b64(json_encode($payload));

        return "$header.$body.".self::sign("$header.$body");
    }

    public static function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new ApiException('Invalid token format');
        }

        [$header, $body, $signature] = $parts;
        if (! hash_equals(self::sign("$header.$body"), $signature)) {
            throw new ApiException('Invalid token signature');
        }

        $payload = json_decode(base64_decode(strtr($body, '-_', '+/')), true);
        if (! is_array($payload) || ($payload['exp'] ?? 0) < time()) {
            throw new ApiException('Token expired');
        }

        return $payload;
    }

    private static function sign(string $data): string
    {
        return self::b64(hash_hmac('sha256', $data, (string) config('services.driver_jwt.secret'), true));
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
