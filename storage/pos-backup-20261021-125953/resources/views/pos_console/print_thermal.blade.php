@php
  $m = fn ($n) => number_format((float) $n, 2);
  $due = (float) $o['total_amount'] + (float) $o['delivery_charge'];
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
  @foreach ($o['items'] as $it)
    <tr><td colspan="3" class="b">{{ $it['name'] }}</td></tr>
    <tr><td>{{ rtrim(rtrim(number_format($it['qty'], 3, '.', ''), '0'), '.') }} × {{ $m($it['price']) }}</td><td>{{ $it['unit'] }}</td><td class="r">{{ $m($it['line']) }}</td></tr>
  @endforeach
</table>
<hr>
<table>
  <tr><td>Items total</td><td class="r">{{ $m($o['total_amount']) }}</td></tr>
  <tr><td>Delivery</td><td class="r">{{ $m($o['delivery_charge']) }}</td></tr>
  @if ($o['discount'])<tr><td>Promo {{ $o['promo'] }}</td><td class="r">-{{ $m($o['discount']) }}</td></tr>@endif
  <tr class="big"><td>TOTAL ₹</td><td class="r">{{ $m($due) }}</td></tr>
  <tr><td>Payment</td><td class="r">{{ $o['payment_type'] === 'Cash' ? 'Cash on delivery' : $o['payment_type'] }} · {{ $o['paid'] ? 'PAID' : 'DUE' }}</td></tr>
  @if ($o['paid'] && $o['change'] > 0)<tr><td>Change given</td><td class="r">{{ $m($o['change']) }}</td></tr>@endif
</table>
<hr>
<div class="bar">Thank you for choosing Freshful!<br>freshful.in · +91 8124200242</div>
@if (request('auto'))<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 250); });</script>@endif
</body>
</html>
