<?php

namespace App\Http\Controllers\PosConsole;

use App\Http\Controllers\Controller;
use App\Http\Controllers\InvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** POS side of invoices: a signed print link for one order or outlet bill of this hub, and a small finder. */
class PosInvoiceController extends Controller
{
    private function hub(Request $r): int { return (int) (((array) $r->attributes->get('pos_actor', []))['hub_id'] ?? 0); }

    public function link(Request $r, string $type, string $id)
    {
        try {
            $hub = $this->hub($r);
            if ($type === 'outlet') {
                if (!Schema::hasTable('outlet_sales') || !DB::table('outlet_sales')->where('id', (int) $id)->where('hub_id', $hub)->exists()) return response()->json(['ok' => false, 'message' => 'Bill not found.']);
            } elseif ($type === 'order') {
                if (!DB::table('orders')->where('order_id', $id)->where('hub_id', $hub)->exists()) return response()->json(['ok' => false, 'message' => 'Order not found at this hub.']);
            } else {
                return response()->json(['ok' => false, 'message' => 'Unknown invoice type.']);
            }
            $fmt = $r->query('fmt') === 'thermal' ? 'thermal' : 'a4';
            return response()->json(['ok' => true, 'url' => InvoiceController::signedUrl($type, $id, $fmt, $r->query('print') === '1')])->header('Cache-Control', 'no-store');
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'message' => 'Could not open the invoice. Please try again.']);
        }
    }

    public function find(Request $r)
    {
        try {
            $hub = $this->hub($r);
            $q = trim((string) $r->query('q', ''));
            $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->query('from')) ? $r->query('from') : null;
            $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->query('to')) ? $r->query('to') : null;
            $kind = in_array($r->query('kind'), ['bills', 'orders'], true) ? $r->query('kind') : 'all';
            $o = DB::table('orders as o')->leftJoin('buyers as b', 'b.id', '=', 'o.buyer_id')->where('o.hub_id', $hub);
            if ($q !== '') {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                $o->where(fn ($w) => $w->where('o.order_id', 'like', $like)->orWhere('b.phone', 'like', $like)->orWhere('b.name', 'like', $like));
            }
            if ($from) $o->where('o.date_added', '>=', $from . ' 00:00:00');
            if ($to) $o->where('o.date_added', '<=', $to . ' 23:59:59');
            $orders = $kind === 'bills' ? [] : $o->groupBy('o.order_id')->orderByRaw('MAX(o.id) DESC')->limit(60)
                ->get(['o.order_id', DB::raw('MAX(o.order_status) as st'), DB::raw('MAX(o.total_amount) as total'), DB::raw('MAX(o.date_added) as at'), DB::raw('MAX(b.name) as name'), DB::raw('MAX(b.phone) as phone')])
                ->map(fn ($x) => ['id' => $x->order_id, 'status' => $x->st, 'total' => (float) $x->total, 'at' => $x->at, 'name' => $x->name, 'phone' => $x->phone])->all();
            if ($kind === 'bills') $orders = [];
            $bills = [];
            if ($kind !== 'orders' && Schema::hasTable('outlet_sales')) {
                $b = DB::table('outlet_sales')->where('hub_id', $hub)->where('status', 'paid');
                if ($q !== '') $b->where('sale_no', 'like', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%');
                if ($from) $b->where('paid_at', '>=', $from . ' 00:00:00');
                if ($to) $b->where('paid_at', '<=', $to . ' 23:59:59');
                $bills = $b->orderByDesc('id')->limit(60)->get(['id', 'sale_no', 'total', 'pay_method', 'paid_at'])
                    ->map(fn ($x) => ['id' => (int) $x->id, 'no' => $x->sale_no, 'total' => (float) $x->total, 'method' => $x->pay_method, 'at' => $x->paid_at])->all();
            }
            return response()->json(['ok' => true, 'orders' => $orders, 'bills' => $bills])->header('Cache-Control', 'no-store');
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'message' => 'Search failed. Please try again.']);
        }
    }
}
