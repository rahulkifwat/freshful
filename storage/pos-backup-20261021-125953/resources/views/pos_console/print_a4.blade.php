@php
  $m = fn ($n) => '₹' . number_format((float) $n, 2);
  $due = (float) $o['total_amount'] + (float) $o['delivery_charge'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice {{ $o['order_id'] }} · Freshful</title>
<style>
  @page { size: A4; margin: 14mm; }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: "Segoe UI", system-ui, -apple-system, Roboto, sans-serif; font-size: 13px; color: #141B2D; background: #fff; }
  .wrap { max-width: 780px; margin: 0 auto; padding: 24px; }
  .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #EA4A1F; padding-bottom: 14px; }
  .brand { font-size: 26px; font-weight: 800; color: #EA4A1F; letter-spacing: -.02em; }
  .muted { color: #6B7280; }
  h2 { margin: 0; font-size: 20px; text-align: right; }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin: 18px 0; }
  .box h4 { margin: 0 0 6px; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: #6B7280; }
  table { width: 100%; border-collapse: collapse; }
  th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #6B7280; border-bottom: 1px solid #E4E9F0; padding: 8px 6px; }
  td { padding: 9px 6px; border-bottom: 1px solid #EEF2F7; }
  .r { text-align: right; }
  .tot { width: 300px; margin-left: auto; margin-top: 12px; }
  .tot td { border: 0; padding: 4px 6px; }
  .tot .g td { font-size: 16px; font-weight: 800; border-top: 2px solid #141B2D; padding-top: 8px; }
  .foot { margin-top: 28px; font-size: 11.5px; color: #6B7280; border-top: 1px solid #E4E9F0; padding-top: 10px; }
  .tools { text-align: right; margin-bottom: 8px; }
  @media print { .tools { display: none; } .wrap { padding: 0; } }
</style>
</head>
<body>
<div class="wrap">
  <div class="tools"><button onclick="window.print()">Print</button></div>
  <div class="head">
    <div><div class="brand">Freshful</div><div class="muted">Fresh meat · seafood · fruits · vegetables</div><div class="muted">talktous@freshful.in · +91 8124200242</div></div>
    <div><h2>Invoice</h2><div class="r"><b>{{ $o['order_id'] }}</b></div><div class="r muted">{{ $o['placed_at'] ? date('d M Y, h:i A', $o['placed_at']) : '' }}</div></div>
  </div>
  <div class="grid">
    <div class="box"><h4>Bill to</h4><b>{{ $o['customer'] }}</b><br>{{ $o['phone'] }}<br>{{ $o['address'] }}</div>
    <div class="box"><h4>Order</h4>
      Hub: <b>{{ $hub->hub ?? '' }}{{ !empty($hub->city) ? ', ' . $hub->city : '' }}</b><br>
      Delivery: {{ $o['delivery_type'] }}{{ $o['slot'] ? ' · ' . $o['slot'] : '' }}<br>
      Payment: {{ $o['payment_type'] === 'Cash' ? 'Cash on delivery' : $o['payment_type'] }} · <b>{{ $o['paid'] ? 'Paid' : 'Due' }}</b>
    </div>
  </div>
  <table>
    <thead><tr><th>#</th><th>Item</th><th>Pack</th><th class="r">Qty</th><th class="r">Rate</th><th class="r">Amount</th></tr></thead>
    <tbody>
    @foreach ($o['items'] as $i => $it)
      <tr><td>{{ $i + 1 }}</td><td><b>{{ $it['name'] }}</b>@if ($it['sku'])<div class="muted" style="font-size:11px">{{ $it['sku'] }}</div>@endif</td><td>{{ $it['unit'] }}</td>
        <td class="r">{{ rtrim(rtrim(number_format($it['qty'], 3, '.', ''), '0'), '.') }}</td><td class="r">{{ $m($it['price']) }}</td><td class="r">{{ $m($it['line']) }}</td></tr>
    @endforeach
    </tbody>
  </table>
  <table class="tot">
    <tr><td>Items total</td><td class="r">{{ $m($o['total_amount']) }}</td></tr>
    <tr><td>Delivery charge</td><td class="r">{{ $m($o['delivery_charge']) }}</td></tr>
    @if ($o['discount'])<tr><td>Promo {{ $o['promo'] }}</td><td class="r">- {{ $m($o['discount']) }}</td></tr>@endif
    <tr class="g"><td>Total</td><td class="r">{{ $m($due) }}</td></tr>
  </table>
  <div class="foot">Thank you for shopping with Freshful. Rates shown are current prices; the order total is the amount agreed at checkout. This is a computer-generated invoice.</div>
</div>
@if (request('auto'))<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 250); });</script>@endif
</body>
</html>
