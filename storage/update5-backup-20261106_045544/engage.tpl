<?php

namespace __NS__;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Customer app: "Notify me" requests, this phone's push address, and the list of messages sent to the customer. */
class AppEngageController extends Controller
{
    private function buyer(Request $request): int
    {
        try {
            $c = app(AppCartController::class);
            $m = new \ReflectionMethod($c, 'ownBuyerId');
            $m->setAccessible(true);
            $id = (int) $m->invoke($c, $request);
            if ($id > 0) {
                return $id;
            }
        } catch (\Throwable $e) {
        }
        foreach ([fn () => $request->attributes->get('buyer_id'), fn () => $request->user() ? $request->user()->id : null, fn () => auth()->id()] as $try) {
            try {
                $v = (int) $try();
                if ($v > 0) {
                    return $v;
                }
            } catch (\Throwable $e) {
            }
        }

        return 0;
    }

    private function fail(string $msg, ?string $code = null)
    {
        return response()->json(['result' => false, 'message' => $msg] + ($code ? ['code' => $code] : []));
    }

    public function stockAlert(Request $request)
    {
        try {
            $buyer = $this->buyer($request);
            if ($buyer < 1) {
                return $this->fail('Please sign in again.', 'unauthorized');
            }
            $pid = (int) $request->input('product_id');
            if ($pid < 1 || ! Schema::hasTable('app_stock_alerts')) {
                return $this->fail('Could not save this right now.');
            }
            $hub = $request->input('hub_id');
            DB::table('app_stock_alerts')->updateOrInsert(['buyer_id' => $buyer, 'product_id' => $pid], ['hub_id' => $hub ? (int) $hub : null, 'notified_at' => null, 'updated_at' => now(), 'created_at' => now()]);

            return response()->json(['result' => true, 'message' => 'Done. We will tell you the moment it is back in stock.']);
        } catch (\Throwable $e) {
            return $this->fail('Could not save this right now. Please try again.');
        }
    }

    public function pushToken(Request $request)
    {
        try {
            $buyer = $this->buyer($request);
            $tok = trim((string) $request->input('token'));
            if ($buyer < 1 || ! preg_match('/^(Expo|Exponent)PushToken\[[^\]]{8,120}\]$/', $tok) || ! Schema::hasTable('app_push_tokens')) {
                return $this->fail('Not saved.');
            }
            DB::table('app_push_tokens')->updateOrInsert(['token' => $tok], ['buyer_id' => $buyer, 'platform' => mb_substr((string) $request->input('platform'), 0, 12), 'updated_at' => now(), 'created_at' => now()]);

            return response()->json(['result' => true, 'message' => 'Saved.']);
        } catch (\Throwable $e) {
            return $this->fail('Not saved.');
        }
    }

    public function notices(Request $request)
    {
        try {
            $buyer = $this->buyer($request);
            if ($buyer < 1 || ! Schema::hasTable('app_notifications')) {
                return response()->json(['result' => true, 'notices' => [], 'unread' => 0]);
            }
            $rows = DB::table('app_notifications')->where('buyer_id', $buyer)->orderByDesc('id')->limit(30)->get();
            $out = $rows->map(fn ($r) => ['id' => (int) $r->id, 'title' => (string) $r->title, 'body' => (string) $r->body, 'kind' => (string) $r->kind, 'item_id' => $r->item_id ? (int) $r->item_id : null, 'created_at' => (string) $r->created_at, 'unread' => $r->read_at === null])->all();
            $unread = $rows->whereNull('read_at')->count();
            DB::table('app_notifications')->where('buyer_id', $buyer)->whereNull('read_at')->update(['read_at' => now()]);

            return response()->json(['result' => true, 'notices' => $out, 'unread' => $unread]);
        } catch (\Throwable $e) {
            return response()->json(['result' => true, 'notices' => [], 'unread' => 0]);
        }
    }
}
