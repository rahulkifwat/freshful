<?php

namespace App\Services\Invoice;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One place that builds the data for every Freshful invoice: online orders (customer, POS, rider)
 * and outlet counter bills. The same data feeds the A4 invoice and the 80 mm till receipt.
 */
class InvoiceData
{
    public static function company(): array
    {
        $phone = trim((string) env('INVOICE_PHONE', env('STAFF_SUPPORT_PHONE', '')));
        if (preg_match('/^[+0\s]*$/', $phone)) $phone = '';
        return [
            'legal'   => (string) env('INVOICE_LEGAL_NAME', 'FRESHFUL DIGITAL FOODS (OPC) PRIVATE LIMITED'),
            'brand'   => 'Freshful',
            'gstin'   => (string) env('INVOICE_GSTIN', '33AAFCF2718L1ZN'),
            'fssai'   => (string) env('INVOICE_FSSAI', '12422999001815'),
            'address' => trim((string) env('INVOICE_ADDRESS', '')),
            'phone'   => $phone,
            'email'   => trim((string) env('INVOICE_EMAIL', env('STAFF_SUPPORT_EMAIL', 'admin@freshful.in'))),
            'web'     => 'freshful.in',
            'logo'    => url('/assets/images/logo.png'),
        ];
    }

    private static function money($v): float { return round((float) $v, 2); }

    private static function hubInfo(int $hubId): array
    {
        $hub = DB::table('hubs')->where('id', $hubId)->first();
        $city = '';
        if ($hub && Schema::hasTable('cities')) $city = (string) DB::table('cities')->where('id', $hub->city_id)->value('city');
        return ['code' => $hub->hub ?? '', 'city' => $city];
    }

    /** Online order (all rows with the same order_id). Null when the order does not exist. */
    public static function order(string $orderId): ?array
    {
        $rows = DB::table('orders as o')->join('products as p', 'p.id', '=', 'o.product_id')
            ->where('o.order_id', $orderId)
            ->get(['o.*', 'p.product_name', 'p.product_unit', 'p.unit_quantity', 'p.MRP', 'p.main_price', 'p.product_image', 'p.product_id as code']);
        if ($rows->isEmpty()) return null;
        $f = $rows->first();
        $buyer = DB::table('buyers')->where('id', $f->buyer_id)->first(['name', 'phone', 'email']);
        $addr = DB::table('buyer_address')->where('id', $f->address_id)->first();
        $items = []; $base = 0.0; $mrpTotal = 0.0;
        foreach ($rows as $r) {
            $qty = (float) $r->quantity; $rate = (float) $r->main_price; $mrp = (float) $r->MRP;
            $items[] = ['product_id' => (int) $r->product_id, 'code' => $r->code, 'name' => trim((string) $r->product_name), 'pack' => trim($r->unit_quantity . ' ' . $r->product_unit),
                'qty' => $qty, 'mrp' => $mrp, 'rate' => $rate, 'amount' => round($rate * $qty, 2), 'image' => $r->product_image];
            $base += $rate * $qty; $mrpTotal += max($mrp, $rate) * $qty;
        }
        $discount = 0.0; $coupon = '';
        try {
            if (Schema::hasTable('buyer_coupon_relation') && Schema::hasTable('home_promotions')) {
                $c = DB::table('home_promotions as t1')->join('buyer_coupon_relation as t2', 't2.promo_id', '=', 't1.id')
                    ->where('t2.user_id', $f->buyer_id)->where('t2.order_id', $orderId)->first(['t1.title', 't2.discount']);
                if ($c) { $coupon = (string) $c->title; $discount = round((float) $c->discount, 2); }
            }
        } catch (\Throwable $e) {}
        $delivery = self::money($f->delivery_charge);
        $total = self::money($f->total_amount);               // items after discount, before delivery (as the old POS read it)
        $adjust = round($total - ($base - $discount), 2);       // weight changes, price changes after ordering
        $net = round($total + $delivery, 2);
        $done = !empty($f->delivery_complete_time) ? $f->delivery_complete_time : null;
        $delivered = $f->order_status === 'Order Delivered';
        $cancelled = stripos((string) $f->order_status, 'cancel') !== false;
        $paid = in_array(strtolower((string) $f->payment_status), ['paid', 'success', 'completed', 'settled'], true);
        $hub = self::hubInfo((int) $f->hub_id);
        return [
            'kind' => 'order', 'id' => $orderId, 'number' => 'INV-' . $orderId, 'date' => $done ?: $f->date_added, 'placed' => $f->date_added,
            'status' => $f->order_status, 'cancelled' => $cancelled, 'delivered' => $delivered,
            'customer' => ['name' => $buyer->name ?? ($addr->name ?? ''), 'phone' => $addr->phone ?? ($buyer->phone ?? ''),
                'address' => trim(implode(', ', array_filter([$addr->street_name ?? '', $addr->locality ?? '', $addr->landmark ?? '', $addr->city ?? ''])))],
            'delivery' => ['type' => $f->delivery_type, 'slot' => trim(($f->schedule_date ?? '') . ' ' . ($f->schedule_time ?? ''))],
            'hub' => $hub, 'items' => $items,
            'totals' => ['base' => round($base, 2), 'mrp_total' => round($mrpTotal, 2), 'saved' => round(max(0, $mrpTotal - $base) + $discount, 2), 'coupon' => $coupon, 'discount' => $discount,
                'adjust' => $adjust, 'cgst' => 0.0, 'sgst' => 0.0, 'delivery' => $delivery, 'net' => $net],
            'payment' => ['method' => $f->payment_type, 'status' => $paid ? 'PAID' : ($cancelled ? 'CANCELLED' : (strtolower((string) $f->payment_type) === 'cash' ? 'CASH ON DELIVERY' : 'PAYMENT PENDING')),
                'ref' => $f->txn_id ?: ($f->payment_id ?? ''), 'tendered' => null, 'change' => null],
            'cashier' => '', 'packs' => (int) array_sum(array_column($items, 'qty')),
        ];
    }

    /** Outlet counter bill by id. */
    public static function outlet(int $saleId): ?array
    {
        if (!Schema::hasTable('outlet_sales')) return null;
        $s = DB::table('outlet_sales')->where('id', $saleId)->first();
        if (!$s) return null;
        $rows = DB::table('outlet_sale_items')->where('sale_id', $saleId)->orderBy('id')->get();
        $items = []; $mrpTotal = 0.0; $base = 0.0;
        foreach ($rows as $i) {
            $mrp = (float) DB::table('products')->where('product_id', $i->product_code)->value('MRP');
            $items[] = ['code' => $i->product_code, 'name' => $i->name, 'pack' => $i->pack_label, 'qty' => (float) $i->qty, 'weight' => $i->weight_g !== null ? (float) $i->weight_g : null,
                'mrp' => max($mrp, (float) $i->rate), 'rate' => (float) $i->rate, 'amount' => (float) $i->amount];
            $base += (float) $i->amount; $mrpTotal += max($mrp, (float) $i->rate) * ($i->weight_g !== null ? 1 : (float) $i->qty);
        }
        $hub = self::hubInfo((int) $s->hub_id);
        return [
            'kind' => 'outlet', 'id' => (string) $s->id, 'number' => $s->sale_no, 'date' => $s->paid_at ?: $s->created_at, 'placed' => $s->created_at,
            'status' => $s->status === 'paid' ? 'Paid at counter' : ucfirst((string) $s->status), 'cancelled' => $s->status === 'void', 'delivered' => true,
            'customer' => ['name' => 'Walk-in customer', 'phone' => '', 'address' => ''],
            'delivery' => ['type' => 'Outlet counter', 'slot' => ''], 'hub' => $hub, 'items' => $items,
            'totals' => ['base' => round($base, 2), 'mrp_total' => round($mrpTotal, 2), 'saved' => round(max(0, $mrpTotal - $base), 2), 'coupon' => '', 'discount' => 0.0,
                'adjust' => 0.0, 'cgst' => 0.0, 'sgst' => 0.0, 'delivery' => 0.0, 'net' => self::money($s->total)],
            'payment' => ['method' => strtoupper((string) $s->pay_method), 'status' => $s->status === 'paid' ? 'PAID' : 'NOT PAID', 'ref' => (string) $s->pay_ref,
                'tendered' => $s->tendered !== null ? (float) $s->tendered : null, 'change' => $s->change_amt !== null ? (float) $s->change_amt : null],
            'cashier' => (string) $s->cashier_name, 'packs' => (int) array_sum(array_map(fn ($x) => $x['weight'] ? 1 : $x['qty'], $items)),
        ];
    }
}
