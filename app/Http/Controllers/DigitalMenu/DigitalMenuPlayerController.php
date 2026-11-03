<?php

namespace App\Http\Controllers\DigitalMenu;

use App\Http\Controllers\Controller;
use App\Services\DigitalMenu\DigitalMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** The customer-facing TV page. No menu, no admin links. Reached only through a private link. */
class DigitalMenuPlayerController extends Controller
{
    private function adminOk(): bool
    {
        try {
            return (bool) Auth::guard('admin')->user();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function show(Request $r, string $key)
    {
        if (! DigitalMenu::tableReady()) {
            abort(404);
        }
        $s = DB::table('dm_screens')->where('public_key', $key)->first(['id', 'name', 'status']);
        if (! $s) {
            abort(404);
        }

        return response()->view('digital_menu.player', [
            'key' => $key, 'preview' => $r->boolean('preview') && $this->adminOk(), 'kiosk' => $r->boolean('kiosk'),
            'logo' => url('/assets/images/freshful-logo.png'),
        ])->header('Cache-Control', 'no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function data(Request $r, string $key)
    {
        try {
            $s = DB::table('dm_screens')->where('public_key', $key)->first();
            if (! $s) {
                return response()->json(['ok' => false, 'state' => 'missing', 'message' => 'This screen link is not valid.'], 404);
            }
            $preview = $r->boolean('preview') && $this->adminOk();
            if ($s->status !== 'active' && ! $preview) {
                return response()->json(['ok' => false, 'state' => 'off', 'message' => 'This screen is switched off.'])->header('Cache-Control', 'no-store');
            }
            $json = $preview ? $s->draft : $s->live;
            if (! $json) {
                return response()->json(['ok' => false, 'state' => 'unpublished', 'message' => 'This screen has not been published yet.'])->header('Cache-Control', 'no-store');
            }
            $cfg = DigitalMenu::sanitize(json_decode($json, true) ?: []);
            $body = DigitalMenu::payload($cfg, (int) $s->hub_id);
            $body['ok'] = true;
            $body['preview'] = $preview;
            $tag = '"' . $body['version'] . '"';
            if (! $preview && trim((string) $r->header('If-None-Match')) === $tag) {
                return response('', 304)->header('ETag', $tag)->header('Cache-Control', 'public, max-age=30');
            }

            return response()->json($body)->header('ETag', $tag)->header('Cache-Control', $preview ? 'no-store' : 'public, max-age=30');
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'state' => 'error', 'message' => 'Could not load the menu. It will retry.'], 500)->header('Cache-Control', 'no-store');
        }
    }
}
