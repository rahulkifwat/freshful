@php
  $m = fn ($n) => ((float) $n < 0 ? '-' : '') . number_format(abs((float) $n), 2);
  $g = fn ($x) => $x >= 1000 ? rtrim(rtrim(number_format($x / 1000, 3, '.', ''), '0'), '.') . ' kg' : round($x) . ' g';
  $settled = $o['stage'] === 'delivered';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Receipt {{ $o['order_id'] }}</title>
<style>
  @page { size: 80mm auto; margin: 3mm; }
  * { box-sizing: border-box; }
  body { margin: 0 auto; width: 74mm; font-family: "Courier New", ui-monospace, monospace; font-size: 12px; color: #000; background: #fff; }
  h1 { font-size: 18px; margin: 0; text-align: center; letter-spacing: .04em; }
  .c { text-align: center; } .r { text-align: right; } .b { font-weight: 700; }
  hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
  table { width: 100%; border-collapse: collapse; }
  td { padding: 2px 0; vertical-align: top; }
  .big { font-size: 15px; font-weight: 700; }
  .bar { margin-top: 8px; text-align: center; font-size: 11px; }
  @media screen { body { padding: 10px; box-shadow: 0 0 0 1px #ddd; margin-top: 12px; } .tools { text-align: center; margin: 10px 0; } }
  @media print { .tools { display: none; } }
</style>
</head>
<body>
<div class="tools"><button onclick="window.print()">Print</button></div>
<h1>FRESHFUL</h1>
<div class="c">Fresh meat · seafood · fruits · vegetables</div>
<div class="c">{{ $hub->hub ?? '' }}{{ !empty($hub->city) ? ', ' . $hub->city : '' }}</div>
<hr>
<table>
  <tr><td>Order</td><td class="r b">{{ $o['order_id'] }}</td></tr>
  <tr><td>Date</td><td class="r">{{ $o['placed_at'] ? date('d/m/Y h:i A', $o['placed_at']) : '' }}</td></tr>
  <tr><td>Type</td><td class="r">{{ $o['delivery_type'] }}{{ $o['slot'] ? ' · ' . $o['slot'] : '' }}</td></tr>
  <tr><td>Customer</td><td class="r">{{ $o['customer'] }}</td></tr>
  <tr><td>Phone</td><td class="r">{{ $o['phone'] }}</td></tr>
</table>
<div style="margin-top:3px">{{ $o['address'] }}</div>
<hr>
<table>
  @foreach ($o['lines'] as $it)
    <tr><td colspan="3" class="b">{{ $it['name'] }}</td></tr>
    <tr><td>{{ rtrim(rtrim(number_format($it['qty'], 3, '.', ''), '0'), '.') }} × {{ $m($it['price']) }}</td><td>{{ $it['unit'] }}</td><td class="r">{{ $m($it['line']) }}</td></tr>
    @if ($it['actual_g'] !== null)<tr><td colspan="2">Weighed {{ $g($it['actual_g']) }} / {{ $g($it['expected_g']) }}</td><td class="r">{{ $it['diff'] >= 0 ? '+' : '' }}{{ $m($it['diff']) }}</td></tr>@endif
  @endforeach
</table>
<hr>
<table>
  @if ($o['discount'])<tr><td>Items (before promo)</td><td class="r">{{ $m($o['total_amount'] + $o['discount']) }}</td></tr>
  <tr><td>Promo {{ $o['promo'] }}</td><td class="r">-{{ $m($o['discount']) }}</td></tr>@endif
  <tr><td>Items{{ $o['discount'] ? ' after promo' : '' }}</td><td class="r">{{ $m($o['total_amount']) }}</td></tr>
  <tr><td>Delivery</td><td class="r">{{ $m($o['delivery_charge']) }}</td></tr>
  <tr><td>Bill</td><td class="r">{{ $m($o['bill']) }}</td></tr>
  @if ($o['adjustment'])<tr><td>Weight adjustment</td><td class="r">{{ $o['adjustment'] > 0 ? '+' : '' }}{{ $m($o['adjustment']) }}</td></tr>@endif
  <tr class="big"><td>TOTAL ₹</td><td class="r">{{ $m($o['amount']) }}</td></tr>
  <tr><td>Payment</td><td class="r">{{ $o['payment_type'] === 'Cash' ? 'Cash on delivery' : $o['payment_type'] }} · {{ $settled ? 'PAID' : ($o['prepaid'] ? 'PREPAID' : 'DUE') }}</td></tr>
  @if ($o['prepaid'] && abs($o['expected']) >= 0.01)<tr class="b"><td>{{ $o['expected'] > 0 ? 'Collect on delivery' : 'Refund to customer' }}</td><td class="r">{{ $m(abs($o['expected'])) }}</td></tr>@endif
  @if (!$o['prepaid'] && !$settled)<tr class="b"><td>Collect on delivery</td><td class="r">{{ $m($o['expected']) }}</td></tr>@endif
  @if ($o['rider'])<tr><td>Rider</td><td class="r">{{ $o['rider'] }}</td></tr>@endif
</table>
<hr>
<div class="bar">Thank you for choosing Freshful!<br>freshful.in · +91 8124200242</div>
@if (request('auto'))<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 250); });</script>@endif
</body>
</html>
