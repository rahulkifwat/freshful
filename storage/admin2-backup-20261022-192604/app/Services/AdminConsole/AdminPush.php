<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryScope;
use Illuminate\Support\Facades\DB;

/**
 * Freshful admin console (admin update 1, 22 Oct 2026): push notifications to every app user.
 * The old admin sent through Google's legacy FCM API (fcm.googleapis.com/fcm/send, server key in the code). Google shut
 * that API down in 2024, so pushes from the old admin have not reached anyone since. This uses the current FCM HTTP v1 API:
 *   .env  FCM_PROJECT_ID=<firebase project id>   FCM_SERVICE_ACCOUNT=/home/u182416408/firebase-service-account.json
 * (the JSON key downloaded from Firebase > Project settings > Service accounts; keep it OUTSIDE public_html).
 * Same message as before: topic "all", data payload {title, body, image}, so the app shows it the way it always did.
 * Each message is saved in push_noti like the old admin did.
 */
class AdminPush
{
    public static function configured(): bool
    {
        $p = (string) config('admin_console.fcm.service_account', '');
        return $p !== '' && is_file($p) && is_readable($p) && (string) self::projectId() !== '';
    }

    private static function account(): ?array
    {
        $p = (string) config('admin_console.fcm.service_account', '');
        if ($p === '' || !is_file($p)) return null;
        $j = json_decode((string) @file_get_contents($p), true);
        return is_array($j) && !empty($j['private_key']) && !empty($j['client_email']) ? $j : null;
    }

    private static function projectId(): string
    {
        $id = trim((string) config('admin_console.fcm.project_id', ''));
        if ($id !== '') return $id;
        $p = (string) config('admin_console.fcm.service_account', '');
        if ($p !== '' && is_file($p)) { $j = json_decode((string) @file_get_contents($p), true); return (string) ($j['project_id'] ?? ''); }
        return '';
    }

    private static function b64(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }

    private static function token(): ?string
    {
        $cached = cache()->get('admin_fcm_token');
        if ($cached) return $cached;
        $a = self::account(); if (!$a) return null;
        $now = time();
        $head = self::b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = self::b64(json_encode(['iss' => $a['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging', 'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600]));
        $sig = '';
        if (!openssl_sign($head . '.' . $claim, $sig, $a['private_key'], OPENSSL_ALGO_SHA256)) return null;
        $jwt = $head . '.' . $claim . '.' . self::b64($sig);
        $r = self::http('https://oauth2.googleapis.com/token', http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt]), ['Content-Type: application/x-www-form-urlencoded']);
        $j = json_decode($r['body'], true);
        if (empty($j['access_token'])) return null;
        cache()->put('admin_fcm_token', $j['access_token'], max(60, (int) ($j['expires_in'] ?? 3600) - 120));
        return $j['access_token'];
    }

    private static function http(string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8]);
        $out = curl_exec($ch); $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        return ['code' => $code, 'body' => (string) $out];
    }

    public static function list(): array
    {
        $rows = [];
        if (CountryScope::has('push_noti')) foreach (DB::table('push_noti')->orderByDesc('id')->limit(300)->get() as $p) {
            $rows[] = ['id' => (int) $p->id, 'title' => (string) $p->title, 'body' => (string) $p->description, 'image' => AdminUploads::url('', $p->img), 'at' => is_numeric($p->strtotime) ? (int) $p->strtotime : CountryScope::ts($p->strtotime)];
        }
        return ['ok' => true, 'rows' => $rows, 'configured' => self::configured(), 'topic' => (string) config('admin_console.fcm.topic', 'all')];
    }

    public static function send(string $title, string $body, string $image, array $actor): array
    {
        $title = trim($title); $body = trim($body); $image = trim($image);
        if ($title === '' || mb_strlen($title) > 80) return ['ok' => false, 'message' => 'Write a title (at most 80 characters).'];
        if ($body === '' || mb_strlen($body) > 400) return ['ok' => false, 'message' => 'Write the message (at most 400 characters).'];
        if ($image !== '' && !preg_match('/^[A-Za-z0-9_\-\.]{1,120}$/', $image)) return ['ok' => false, 'message' => 'Upload the image again.'];
        if (!self::configured()) return ['ok' => false, 'message' => 'Push is not set up yet: add the Firebase key (FCM_SERVICE_ACCOUNT and FCM_PROJECT_ID in .env). See the steps on this screen.'];
        $last = CountryScope::has('push_noti') ? DB::table('push_noti')->orderByDesc('id')->first() : null;
        if ($last && is_numeric($last->strtotime) && time() - (int) $last->strtotime < 60 && (string) $last->title === $title) return ['ok' => false, 'message' => 'This message was just sent. Wait a minute before sending it again.'];
        $tok = self::token();
        if (!$tok) return ['ok' => false, 'message' => 'Google did not accept the Firebase key. Check the service account file.'];
        $imgUrl = $image !== '' ? AdminUploads::url('', $image) : '';
        $msg = ['topic' => (string) config('admin_console.fcm.topic', 'all'), 'data' => ['title' => $title, 'body' => $body, 'image' => $imgUrl], 'android' => ['priority' => 'high']];
        if (config('admin_console.fcm.mode', 'data') === 'both') $msg['notification'] = array_filter(['title' => $title, 'body' => $body, 'image' => $imgUrl]);
        $r = self::http('https://fcm.googleapis.com/v1/projects/' . rawurlencode(self::projectId()) . '/messages:send', json_encode(['message' => $msg]), ['Content-Type: application/json', 'Authorization: Bearer ' . $tok]);
        if ($r['code'] !== 200) {
            $err = json_decode($r['body'], true)['error']['message'] ?? ('HTTP ' . $r['code']);
            return ['ok' => false, 'message' => 'Google refused the message: ' . mb_substr((string) $err, 0, 160)];
        }
        $id = 0;
        if (CountryScope::has('push_noti')) $id = (int) DB::table('push_noti')->insertGetId(['title' => $title, 'img' => $image, 'description' => $body, 'strtotime' => (string) time()]);
        AdminAudit::log($actor, 'send', 'Marketing', 'push_noti', $id, 'Sent push "' . $title . '" to all app users', null, ['title' => $title, 'body' => $body, 'image' => $image]);
        return ['ok' => true, 'message' => 'Sent to every app user.'];
    }
}
