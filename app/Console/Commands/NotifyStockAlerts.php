<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

// Sends "back in stock" alerts to customers who tapped Notify me in the app. Runs every few minutes.
class NotifyStockAlerts extends Command
{
    protected $signature = 'app:notify-stock-alerts {--dry : only show what would be sent}';
    protected $description = 'Notify app customers when an out-of-stock item is available again';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $alerts = DB::table('app_stock_alerts')->whereNull('notified_at')->get();
        if ($alerts->isEmpty()) {
            $this->info('No pending alerts.');
            return self::SUCCESS;
        }
        $live = [];
        foreach ($alerts->pluck('hub_id')->unique() as $hubId) {
            $raw = [];
            foreach (DB::table('inventory')->where('hub_id', $hubId)->orderBy('id')->get(['product_id', 'live_stock']) as $r) {
                if (! isset($raw[(string) $r->product_id])) {
                    $raw[(string) $r->product_id] = (float) $r->live_stock;
                }
            }
            $live[(int) $hubId] = \App\Services\Stock\StockGuard::net((int) $hubId, $raw);
        }
        $sent = 0;
        foreach ($alerts as $a) {
            try {
                $p = DB::table('products as p')->join('items as i', 'i.id', '=', 'p.item_id')
                    ->where('p.id', $a->product_id)->first(['p.product_id as code', 'p.item_id', 'p.status', 'i.item as item_name']);
                if (! $p || $p->status !== 'active') {
                    continue;
                }
                $avail = (float) ($live[(int) $a->hub_id][(string) $p->code] ?? 0);
                if ($avail <= 0) {
                    continue;
                }
                $title = 'Back in stock';
                $body = trim((string) $p->item_name).' is available again. Order now before it sells out.';
                $this->line("buyer {$a->buyer_id}: {$p->item_name} ({$p->code}) avail {$avail}");
                if ($dry) {
                    continue;
                }
                DB::table('app_notifications')->insert([
                    'buyer_id' => $a->buyer_id, 'kind' => 'stock', 'title' => $title, 'body' => $body,
                    'item_id' => (int) $p->item_id, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $pushed = $this->push((int) $a->buyer_id, $title, $body, (int) $p->item_id);
                if ($pushed) {
                    DB::table('app_notifications')->where('buyer_id', $a->buyer_id)->where('item_id', $p->item_id)->whereNull('pushed_at')->update(['pushed_at' => now()]);
                }
                DB::table('app_stock_alerts')->where('id', $a->id)->update(['notified_at' => now(), 'updated_at' => now()]);
                $sent++;
            } catch (Throwable $e) {
                Log::warning('[stock-alert] '.$e->getMessage());
            }
        }
        $this->info("Notified: {$sent}");
        return self::SUCCESS;
    }

    private function push(int $buyerId, string $title, string $body, int $itemId): bool
    {
        $tokens = DB::table('app_push_tokens')->where('buyer_id', $buyerId)->pluck('token')->all();
        if (! $tokens) {
            return false;
        }
        $msgs = array_map(fn ($t) => ['to' => $t, 'title' => $title, 'body' => $body, 'sound' => 'default', 'channelId' => 'default', 'data' => ['item_id' => $itemId, 'kind' => 'stock']], $tokens);
        try {
            $res = Http::timeout(15)->acceptJson()->post('https://exp.host/--/api/v2/push/send', $msgs)->json();
            $ok = false;
            foreach (($res['data'] ?? []) as $i => $t) {
                if (($t['status'] ?? '') === 'ok') {
                    $ok = true;
                } elseif (($t['details']['error'] ?? '') === 'DeviceNotRegistered' && isset($tokens[$i])) {
                    DB::table('app_push_tokens')->where('token', $tokens[$i])->delete();
                }
            }
            return $ok;
        } catch (Throwable $e) {
            Log::warning('[stock-alert push] '.$e->getMessage());
            return false;
        }
    }
}
