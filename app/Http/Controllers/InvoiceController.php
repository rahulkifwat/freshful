<?php

namespace App\Http\Controllers;

use App\Services\Invoice\InvoiceData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * Customer side of invoices: the invoice of an order (login required), the signed share link
 * (works without login, for POS printing and for sending to the customer), and the order details page.
 */
class InvoiceController extends Controller
{
    private function page(array $inv, Request $r, ?string $signedType = null, ?string $signedId = null)
    {
        $fmt = $r->query('fmt') === 'thermal' ? 'thermal' : 'a4';
        $other = $fmt === 'thermal' ? 'a4' : 'thermal';
        $switch = $signedType ? self::signedUrl($signedType, (string) $signedId, $other) : $r->fullUrlWithQuery(['fmt' => $other, 'print' => null]);
        return response()->view('invoice.show', [
            'inv' => $inv, 'co' => InvoiceData::company(), 'fmt' => $fmt, 'switch' => $switch, 'autoprint' => (bool) $r->query('print'),
        ])->header('Cache-Control', 'no-store')->header('X-Robots-Tag', 'noindex');
    }

    private function owns(string $orderId): bool
    {
        return Auth::check() && DB::table('orders')->where('order_id', $orderId)->where('buyer_id', Auth::id())->exists();
    }

    /** /invoice/{orderId}  (customer, logged in) */
    public function order(Request $r, string $orderId)
    {
        if (!Auth::check()) return redirect()->route('home')->with('error', 'Please login first');
        if (!$this->owns($orderId)) abort(404);
        $inv = InvoiceData::order($orderId);
        abort_if(!$inv, 404);
        return $this->page($inv, $r);
    }

    /** /invoice/s/{type}/{id}  signed link: order or outlet bill */
    public function signed(Request $r, string $type, string $id)
    {
        $inv = $type === 'outlet' ? InvoiceData::outlet((int) $id) : ($type === 'order' ? InvoiceData::order($id) : null);
        abort_if(!$inv, 404);
        return $this->page($inv, $r, $type, $id);
    }

    public static function signedUrl(string $type, string $id, string $fmt = 'a4', bool $print = false, ?\DateTimeInterface $until = null): string
    {
        $p = ['type' => $type, 'id' => $id, 'fmt' => $fmt === 'thermal' ? 'thermal' : 'a4'];
        if ($print) $p['print'] = 1;
        return URL::temporarySignedRoute('invoice.signed', $until ?: now()->addDays($type === 'outlet' ? 2 : 30), $p);
    }

    /** /account/orders/{orderId}  details, invoice and a rating for every item */
    public function details(Request $r, string $orderId)
    {
        if (!Auth::check()) return redirect()->route('home')->with('error', 'Please login first');
        if (!$this->owns($orderId)) abort(404);
        $inv = InvoiceData::order($orderId);
        abort_if(!$inv, 404);
        $mine = DB::table('reviews')->where('buyer_id', Auth::id())->whereIn('product_id', array_column($inv['items'], 'product_id'))->get()->keyBy('product_id');
        return response()->view('account.order_details', ['inv' => $inv, 'co' => InvoiceData::company(), 'reviews' => $mine])->header('Cache-Control', 'no-store');
    }
}
