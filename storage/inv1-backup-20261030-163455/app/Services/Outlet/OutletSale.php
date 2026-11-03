<?php

namespace App\Services\Outlet;

use App\Services\HubConsole\HubStock;
use App\Services\Stock\StockGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Outlet counter sale (30 Oct 2026): scan only, no typing of products or prices.
 *
 *  scan    a barcode becomes a bill line. Three kinds are understood:
 *            1. a barcode learned in product_barcodes (maker's barcode or one printed by us),
 *            2. a scale label EAN-13 "2 PPPPP WWWWW C" (PPPPP = label code of the product, WWWWW = grams),
 *            3. the product code itself (PRD00075).
 *  hold    while the bill is open its packs are held (StockGuard), so online customers cannot take them.
 *  pay     UPI QR, cash or card. In ONE transaction: every line is checked against sellable stock under row locks,
 *          then HubStock::adjust takes the packs off the shelf (ledger type "sale", ref = bill number).
 * Stock counts whole packs, exactly as the rest of the system.
 */
class OutletSale
{
    public const METHODS = ['upi', 'cash', 'card'];

    private int $hubId;
    private array $actor;

    public function __construct(int $hubId, array $actor = [])
    {
        $this->hubId = $hubId;
        $this->actor = $actor;
    }

    public static function ready(): bool
    {
        return Schema::hasTable('outlet_sales') && Schema::hasTable('outlet_sale_items') && Schema::hasTable('product_barcodes');
    }

    private function err(string $m): array { return ['ok' => false, 'message' => $m]; }

    private function cashier(): array
    {
        return [(int) ($this->actor['id'] ?? 0), mb_substr((string) ($this->actor['name'] ?? ''), 0, 120)];
    }

    /* ------------------------------------------------------------------ reading a barcode */

    public static function ean13Ok(string $d): bool
    {
        if (!preg_match('/^\d{13}$/', $d)) return false;
        $s = 0;
        for ($i = 0; $i < 12; $i++) $s += (int) $d[$i] * ($i % 2 ? 3 : 1);
        return ((10 - $s % 10) % 10) === (int) $d[12];
    }

    /** Pack size in grams or millilitres from the product unit ("500" + "Gram"); null when it is a count. */
    public static function packBase($qty, $unit): ?float
    {
        $q = (float) preg_replace('/[^0-9.]/', '', (string) $qty);
        $u = strtolower(trim((string) $unit));
        if ($q <= 0) return null;
        if (in_array($u, ['g', 'gm', 'gms', 'gram', 'grams', 'ml', 'millilitre', 'milliliter'], true)) return $q;
        if (in_array($u, ['kg', 'kgs', 'kilogram', 'kilograms', 'l', 'ltr', 'litre', 'liter', 'litres', 'liters'], true)) return $q * 1000;
        return null;
    }

    /** @return array ok, product (row), kind (pack|weighed), weight (grams or null), barcode */
    public function resolve(string $raw): array
    {
        $b = preg_replace('/[^0-9A-Za-z\-_.]/', '', trim($raw));
        if ($b === '' || strlen($b) > 64) return $this->err('Scan the product barcode.');
        $code = null; $kind = 'pack'; $weight = null;

        $row = DB::table('product_barcodes')->where('barcode', $b)->where('kind', 'pack')->first();
        if ($row) $code = (string) $row->product_code;

        if ($code === null && preg_match('/^2\d{12}$/', $b)) {
            if (!self::ean13Ok($b)) return $this->err('This scale label is damaged. Print it again.');
            $plu = substr($b, 1, 5);
            $lab = DB::table('product_barcodes')->where('barcode', $plu)->where('kind', 'weighed')->first();
            if (!$lab) return $this->unknown($b, 'This scale label code (' . $plu . ') is not linked to a product yet.');
            $code = (string) $lab->product_code; $kind = 'weighed'; $weight = (float) (int) substr($b, 6, 5);
            if ($weight <= 0) return $this->err('The label has no weight.');
        }
        if ($code === null) {
            $p = DB::table('products')->whereRaw('UPPER(TRIM(product_id)) = ?', [strtoupper($b)])->first();
            if ($p) $code = (string) $p->product_id;
        }
        if ($code === null) return $this->unknown($b, 'This barcode is not linked to a product yet.');

        $p = DB::table('products')->where('product_id', $code)->first();
        if (!$p) return $this->err('The product for this barcode no longer exists.');
        if (strtolower((string) $p->status) !== 'active') return $this->err(trim((string) $p->product_name) . ' is switched off in the catalogue.');
        return ['ok' => true, 'product' => $p, 'kind' => $kind, 'weight' => $weight, 'barcode' => $b];
    }

    private function unknown(string $b, string $m): array
    {
        return ['ok' => false, 'unknown' => $b, 'message' => $m];
    }

    /* ------------------------------------------------------------------ the bill */

    private function number(): string
    {
        $prefix = 'OS' . date('ymd') . '-';
        $last = DB::table('outlet_sales')->where('sale_no', 'like', $prefix . '%')->orderByDesc('id')->lockForUpdate()->value('sale_no');
        $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
        return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }

    private function lines(int $saleId): array
    {
        return DB::table('outlet_sale_items')->where('sale_id', $saleId)->orderBy('id')->get()->map(fn ($i) => [
            'id' => (int) $i->id, 'code' => $i->product_code, 'name' => $i->name, 'pack' => $i->pack_label, 'qty' => (int) $i->qty,
            'weight' => $i->weight_g !== null ? (float) $i->weight_g : null, 'rate' => (float) $i->rate, 'amount' => (float) $i->amount, 'barcode' => $i->barcode,
        ])->all();
    }

    private function view(object $s): array
    {
        $items = $this->lines((int) $s->id);
        return ['id' => (int) $s->id, 'sale_no' => $s->sale_no, 'status' => $s->status, 'total' => round((float) $s->total, 2), 'method' => $s->pay_method, 'ref' => $s->pay_ref,
            'tendered' => $s->tendered !== null ? (float) $s->tendered : null, 'change' => $s->change_amt !== null ? (float) $s->change_amt : null, 'paid_at' => $s->paid_at, 'cashier' => $s->cashier_name,
            'items' => $items, 'packs' => array_sum(array_column($items, 'qty'))];
    }

    private function mine(int $saleId, bool $lock = false): ?object
    {
        $q = DB::table('outlet_sales')->where('id', $saleId)->where('hub_id', $this->hubId);
        if ($lock) $q->lockForUpdate();
        return $q->first();
    }

    private function recount(int $saleId): void
    {
        $sum = (float) DB::table('outlet_sale_items')->where('sale_id', $saleId)->sum('amount');
        DB::table('outlet_sales')->where('id', $saleId)->update(['subtotal' => round($sum, 2), 'total' => round($sum, 2), 'updated_at' => now()]);
    }

    /** The cashier's open bill, if any (so a page reload does not lose it). */
    public function current(): array
    {
        [$cid] = $this->cashier();
        $s = DB::table('outlet_sales')->where('hub_id', $this->hubId)->where('status', 'open')->where('cashier_id', $cid)
            ->where('updated_at', '>=', date('Y-m-d H:i:s', time() - 6 * 3600))->orderByDesc('id')->first();
        return ['ok' => true, 'sale' => $s ? $this->view($s) : null, 'upi' => self::upi()];
    }

    public static function upi(): array
    {
        return ['vpa' => (string) env('OUTLET_UPI_VPA', ''), 'name' => (string) env('OUTLET_UPI_NAME', 'Freshful')];
    }

    public function scan(?int $saleId, string $raw): array
    {
        $r = $this->resolve($raw);
        if (!$r['ok']) return $r;
        $p = $r['product']; $code = (string) $p->product_id;
        $packBase = self::packBase($p->unit_quantity, $p->product_unit);
        $rate = (float) $p->main_price;
        if ($rate <= 0) return $this->err(trim((string) $p->product_name) . ' has no price in the catalogue.');
        if ($r['kind'] === 'weighed' && !$packBase) return $this->err('The pack size of ' . trim((string) $p->product_name) . ' is not in grams, kg, ml or litres, so a weighed label cannot be priced.');
        $amount = $r['kind'] === 'weighed' ? round($rate * $r['weight'] / $packBase, 2) : $rate;
        $pack = trim($p->unit_quantity . ' ' . $p->product_unit);

        return DB::transaction(function () use ($saleId, $r, $p, $code, $rate, $amount, $pack) {
            $s = $saleId ? $this->mine($saleId, true) : null;
            if ($saleId && (!$s || $s->status !== 'open')) return $this->err('This bill is closed. Start a new bill.');
            if (!$s) {
                [$cid, $cname] = $this->cashier();
                $id = DB::table('outlet_sales')->insertGetId(['sale_no' => $this->number(), 'hub_id' => $this->hubId, 'cashier_id' => $cid, 'cashier_name' => $cname, 'status' => 'open', 'channel' => 'counter',
                    'subtotal' => 0, 'gst' => 0, 'total' => 0, 'created_at' => now(), 'updated_at' => now()]);
                $s = $this->mine($id, true);
            }
            $inBill = (int) DB::table('outlet_sale_items')->where('sale_id', $s->id)->where('product_code', $code)->sum('qty');
            $free = StockGuard::sellable($this->hubId, $code, true, (int) $s->id);
            if ($free - $inBill < 1 - 0.0005) {
                return $this->err(trim((string) $p->product_name) . ($free <= 0 ? ' is out of stock.' : ': only ' . rtrim(rtrim(number_format($free, 3, '.', ''), '0'), '.') . ' pack(s) are free and all of them are already in this bill.'));
            }
            if ($r['kind'] === 'weighed') {
                if (DB::table('outlet_sale_items')->where('sale_id', $s->id)->where('barcode', $r['barcode'])->exists()) return $this->err('This label is already in the bill.');
                DB::table('outlet_sale_items')->insert(['sale_id' => $s->id, 'product_code' => $code, 'product_id' => (int) $p->id, 'name' => mb_substr(trim((string) $p->product_name), 0, 200), 'pack_label' => $pack,
                    'qty' => 1, 'weight_g' => $r['weight'], 'rate' => $rate, 'amount' => $amount, 'barcode' => $r['barcode'], 'created_at' => now()]);
            } else {
                $line = DB::table('outlet_sale_items')->where('sale_id', $s->id)->where('product_code', $code)->whereNull('weight_g')->first();
                if ($line) {
                    $q = (int) $line->qty + 1;
                    DB::table('outlet_sale_items')->where('id', $line->id)->update(['qty' => $q, 'amount' => round($rate * $q, 2)]);
                } else {
                    DB::table('outlet_sale_items')->insert(['sale_id' => $s->id, 'product_code' => $code, 'product_id' => (int) $p->id, 'name' => mb_substr(trim((string) $p->product_name), 0, 200), 'pack_label' => $pack,
                        'qty' => 1, 'weight_g' => null, 'rate' => $rate, 'amount' => $rate, 'barcode' => $r['barcode'], 'created_at' => now()]);
                }
            }
            $this->recount((int) $s->id);
            return ['ok' => true, 'added' => trim((string) $p->product_name), 'sale' => $this->view($this->mine((int) $s->id))];
        });
    }

    public function setQty(int $saleId, int $itemId, int $qty): array
    {
        return DB::transaction(function () use ($saleId, $itemId, $qty) {
            $s = $this->mine($saleId, true);
            if (!$s || $s->status !== 'open') return $this->err('This bill is closed.');
            $i = DB::table('outlet_sale_items')->where('sale_id', $saleId)->where('id', $itemId)->first();
            if (!$i) return $this->err('Line not found.');
            if ($qty <= 0) { DB::table('outlet_sale_items')->where('id', $itemId)->delete(); }
            else {
                if ($i->weight_g !== null) return $this->err('A weighed pack is one pack. Remove it or scan another label.');
                $others = (int) DB::table('outlet_sale_items')->where('sale_id', $saleId)->where('product_code', $i->product_code)->where('id', '!=', $itemId)->sum('qty');
                $free = StockGuard::sellable($this->hubId, (string) $i->product_code, true, $saleId);
                if ($qty + $others > $free + 0.0005) return $this->err('Only ' . rtrim(rtrim(number_format($free, 3, '.', ''), '0'), '.') . ' pack(s) of ' . $i->name . ' are free.');
                DB::table('outlet_sale_items')->where('id', $itemId)->update(['qty' => $qty, 'amount' => round((float) $i->rate * $qty, 2)]);
            }
            $this->recount($saleId);
            return ['ok' => true, 'sale' => $this->view($this->mine($saleId))];
        });
    }

    public function cancel(int $saleId): array
    {
        return DB::transaction(function () use ($saleId) {
            $s = $this->mine($saleId, true);
            if (!$s) return $this->err('Bill not found.');
            if ($s->status !== 'open') return $this->err('Only an open bill can be cancelled.');
            DB::table('outlet_sales')->where('id', $saleId)->update(['status' => 'void', 'updated_at' => now()]);
            return ['ok' => true];
        });
    }

    /** UPI payment string for a QR. Empty when the shop UPI id is not set. */
    public static function upiLink(float $amount, string $saleNo): string
    {
        $u = self::upi();
        if ($u['vpa'] === '') return '';
        return 'upi://pay?pa=' . rawurlencode($u['vpa']) . '&pn=' . rawurlencode($u['name']) . '&am=' . number_format($amount, 2, '.', '') . '&cu=INR&tn=' . rawurlencode($saleNo) . '&tr=' . rawurlencode($saleNo);
    }

    public function pay(int $saleId, string $method, ?string $ref, ?float $tendered): array
    {
        $method = strtolower(trim($method));
        if (!in_array($method, self::METHODS, true)) return $this->err('Choose UPI, cash or card.');
        $ref = $ref !== null ? mb_substr(trim($ref), 0, 60) : null;
        return DB::transaction(function () use ($saleId, $method, $ref, $tendered) {
            $s = $this->mine($saleId, true);
            if (!$s) return $this->err('Bill not found.');
            if ($s->status === 'paid') return ['ok' => true, 'sale' => $this->view($s), 'again' => true];
            if ($s->status !== 'open') return $this->err('This bill is closed.');
            $items = DB::table('outlet_sale_items')->where('sale_id', $saleId)->get();
            if ($items->isEmpty()) return $this->err('The bill is empty. Scan a product first.');
            $total = round((float) $items->sum('amount'), 2);
            if ($method === 'cash' && ($tendered === null || $tendered + 0.005 < $total)) return $this->err('The cash given is less than the total.');

            $need = [];
            foreach ($items as $i) $need[(string) $i->product_code] = ($need[(string) $i->product_code] ?? 0) + (int) $i->qty;
            ksort($need);
            $name = [];
            foreach ($items as $i) $name[(string) $i->product_code] = $i->name;
            foreach ($need as $code => $q) {
                $free = StockGuard::sellable($this->hubId, $code, true, $saleId);
                if ($q > $free + 0.0005) return $this->err(($name[$code] ?? $code) . ': only ' . rtrim(rtrim(number_format($free, 3, '.', ''), '0'), '.') . ' pack(s) are free now. Reduce the quantity.');
            }
            foreach ($need as $code => $q) HubStock::adjust($this->hubId, $code, -(float) $q, 'sale', $s->sale_no, 'Outlet sale ' . $s->sale_no, $this->actor);
            $change = $method === 'cash' ? round((float) $tendered - $total, 2) : null;
            DB::table('outlet_sales')->where('id', $saleId)->update(['status' => 'paid', 'pay_method' => $method, 'pay_ref' => $ref, 'tendered' => $method === 'cash' ? $tendered : null,
                'change_amt' => $change, 'subtotal' => $total, 'total' => $total, 'paid_at' => now(), 'updated_at' => now()]);
            return ['ok' => true, 'sale' => $this->view($this->mine($saleId))];
        });
    }

    public function show(int $saleId): array
    {
        $s = $this->mine($saleId);
        return $s ? ['ok' => true, 'sale' => $this->view($s), 'hub' => (string) DB::table('hubs')->where('id', $this->hubId)->value('hub')] : $this->err('Bill not found.');
    }

    /** Today at this outlet counter. */
    public function summary(): array
    {
        $from = date('Y-m-d 00:00:00');
        $paid = DB::table('outlet_sales')->where('hub_id', $this->hubId)->where('status', 'paid')->where('paid_at', '>=', $from)->get();
        $by = ['upi' => ['n' => 0, 'v' => 0.0], 'cash' => ['n' => 0, 'v' => 0.0], 'card' => ['n' => 0, 'v' => 0.0]];
        foreach ($paid as $s) { $m = $s->pay_method; if (isset($by[$m])) { $by[$m]['n']++; $by[$m]['v'] += (float) $s->total; } }
        foreach ($by as $k => $x) $by[$k]['v'] = round($x['v'], 2);
        $recent = DB::table('outlet_sales')->where('hub_id', $this->hubId)->where('status', 'paid')->orderByDesc('id')->limit(15)->get(['id', 'sale_no', 'total', 'pay_method', 'paid_at', 'cashier_name'])
            ->map(fn ($s) => ['id' => (int) $s->id, 'sale_no' => $s->sale_no, 'total' => (float) $s->total, 'method' => $s->pay_method, 'at' => $s->paid_at, 'cashier' => $s->cashier_name])->all();
        return ['ok' => true, 'today' => ['bills' => $paid->count(), 'total' => round((float) $paid->sum('total'), 2), 'by' => $by], 'recent' => $recent];
    }

    /* ------------------------------------------------------------------ learning a barcode */

    public function searchProducts(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) return ['ok' => true, 'items' => []];
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        $rows = DB::table('products')->where('status', 'active')->where(fn ($w) => $w->where('product_name', 'like', $like)->orWhere('product_id', 'like', $like))
            ->orderBy('product_name')->limit(12)->get(['product_id', 'product_name', 'unit_quantity', 'product_unit', 'main_price']);
        return ['ok' => true, 'items' => $rows->map(fn ($p) => ['code' => $p->product_id, 'name' => trim((string) $p->product_name), 'pack' => trim($p->unit_quantity . ' ' . $p->product_unit), 'price' => (float) $p->main_price])->all()];
    }

    /** Link a barcode to a product once. A 5-digit scale label code is linked as "weighed". */
    public function link(string $barcode, string $code, bool $weighed): array
    {
        $b = preg_replace('/[^0-9A-Za-z\-_.]/', '', trim($barcode));
        if ($b === '') return $this->err('Barcode is empty.');
        if (!DB::table('products')->where('product_id', $code)->exists()) return $this->err('Product not found.');
        if ($weighed) {
            if (preg_match('/^2\d{12}$/', $b)) $b = substr($b, 1, 5);
            if (!preg_match('/^\d{5}$/', $b)) return $this->err('A scale label code is 5 digits.');
        }
        $kind = $weighed ? 'weighed' : 'pack';
        $old = DB::table('product_barcodes')->where('barcode', $b)->where('kind', $kind)->first();
        if ($old) {
            if ($old->product_code === $code) return ['ok' => true, 'message' => 'Already linked.'];
            return $this->err('This barcode already belongs to another product (' . $old->product_code . ').');
        }
        DB::table('product_barcodes')->insert(['barcode' => $b, 'product_code' => $code, 'kind' => $kind, 'created_by' => $this->actor['name'] ?? null, 'created_at' => now()]);
        return ['ok' => true, 'message' => 'Linked.'];
    }
}
