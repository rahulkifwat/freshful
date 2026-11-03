@php
    $fmt = ($fmt ?? 'a4') === 'thermal' ? 'thermal' : 'a4';
    $m = fn ($v) => '&#8377;' . number_format((float) $v, 2);
    $q = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, '.', ''), '0'), '.');
    $t = $inv['totals']; $pay = $inv['payment'];
    $dt = $inv['date'] ? \Carbon\Carbon::parse($inv['date'])->format('d M Y, h:i A') : '';
    $hubLine = trim(($inv['hub']['city'] ?? '') . ($inv['hub']['code'] ? ' (' . $inv['hub']['code'] . ')' : ''));
    $isOutlet = $inv['kind'] === 'outlet';
    $title = $isOutlet ? 'TAX INVOICE / CASH MEMO' : 'TAX INVOICE';
    $stamp = $pay['status'];
    $stampBad = in_array($stamp, ['CANCELLED', 'NOT PAID', 'PAYMENT PENDING'], true);
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $inv['number'] }} - Freshful invoice</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#EEF1F5;color:#111827;font:13px/1.45 -apple-system,"Segoe UI",Roboto,Arial,sans-serif}
.bar{position:sticky;top:0;z-index:5;display:flex;gap:8px;align-items:center;justify-content:center;padding:10px;background:#111827}
.bar a,.bar button{font:600 13px inherit;font-family:inherit;color:#fff;background:transparent;border:1px solid #4B5563;border-radius:8px;padding:9px 16px;cursor:pointer;text-decoration:none}
.bar .pri{background:#F70202;border-color:#F70202}
.sheet{background:#fff;margin:18px auto;padding:34px 38px;box-shadow:0 2px 14px rgba(17,24,39,.12)}
.a4 .sheet{width:210mm;min-height:297mm}
.thermal .sheet{width:80mm;margin:14px auto;padding:10px 8px;font-size:11.5px;box-shadow:none}
.head{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;border-bottom:3px solid #F70202;padding-bottom:16px}
.head img{height:68px;display:block}
.co{text-align:right;font-size:12px;color:#374151}
.co b{display:block;font-size:14px;color:#111827;margin-bottom:2px}
.ttl{display:flex;justify-content:space-between;align-items:center;margin:18px 0 14px}
.ttl h1{margin:0;font-size:19px;letter-spacing:.6px}
.stamp{border:2px solid #111827;border-radius:6px;padding:4px 12px;font-weight:800;letter-spacing:1px;font-size:12px;color:#111827}
.stamp.bad{border-color:#B91C1C;color:#B91C1C}
.grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px}
.box{border:1px solid #E5E7EB;border-radius:8px;padding:10px 12px}
.box h4{margin:0 0 5px;font-size:10.5px;text-transform:uppercase;letter-spacing:.7px;color:#6B7280}
.box div{font-size:12.5px}
table{width:100%;border-collapse:collapse}
.items th{background:#111827;color:#fff;font-size:10.5px;text-transform:uppercase;letter-spacing:.6px;text-align:left;padding:8px 8px}
.items td{padding:9px 8px;border-bottom:1px solid #E5E7EB;vertical-align:top}
.items .n{text-align:right;white-space:nowrap}
.items small{color:#6B7280}
.tot{width:46%;margin:14px 0 0 auto}
.tot td{padding:5px 8px}
.tot .n{text-align:right}
.tot .net td{border-top:2px solid #111827;font-size:16px;font-weight:800;padding-top:9px}
.save{margin-top:12px;border:1px dashed #F70202;color:#B91C1C;border-radius:8px;padding:8px 12px;font-weight:700;text-align:center}
.foot{margin-top:26px;padding-top:12px;border-top:1px solid #E5E7EB;font-size:11.5px;color:#4B5563;display:flex;justify-content:space-between;gap:16px}
.thanks{text-align:center;margin-top:14px;font-weight:700}
.tiny{font-size:10.5px;color:#6B7280;margin-top:8px}
.thermal .head{flex-direction:column;align-items:center;text-align:center;gap:6px;border-bottom:2px dashed #111827;padding-bottom:8px}
.thermal .head img{height:46px}.thermal .co{text-align:center}
.thermal .grid{grid-template-columns:1fr;gap:6px}.thermal .box{border:0;border-bottom:1px dashed #9CA3AF;border-radius:0;padding:4px 0}
.thermal .items th{background:#fff;color:#111827;border-bottom:1px solid #111827;padding:4px 2px}.thermal .items td{padding:4px 2px}
.thermal .items th:nth-child(4),.thermal .items td:nth-child(4){display:none}.thermal .tot{width:100%}.thermal .ttl{margin:8px 0}.thermal .ttl h1{font-size:13px}.thermal .foot{flex-direction:column;border-top:1px dashed #111827}
@media(max-width:700px){.a4 .sheet{width:auto;margin:10px;padding:18px}.grid{grid-template-columns:1fr}.tot{width:100%}}
@page{margin:8mm}
@media print{body{background:#fff}.bar{display:none}.sheet{margin:0;box-shadow:none}.a4 .sheet{width:auto;min-height:0}.thermal .sheet{width:72mm}}
@if ($fmt === 'thermal')@media print{@page{size:80mm auto;margin:3mm}}@endif
</style>
</head>
<body class="{{ $fmt }}">
<div class="bar">
  <button type="button" class="pri" onclick="window.print()">Print / Save as PDF</button>
  <a href="{{ $switch }}">{{ $fmt === 'thermal' ? 'A4 invoice' : 'Till receipt' }}</a>
  <button type="button" onclick="window.close();if(!window.closed)history.back()">Close</button>
</div>
<div class="sheet">
  <div class="head">
    <img src="{{ $co['logo'] }}" alt="Freshful">
    <div class="co">
      <b>{{ $co['legal'] }}</b>
      @if ($co['address']){{ $co['address'] }}<br>@endif
      GSTIN: {{ $co['gstin'] }} &nbsp;|&nbsp; FSSAI Lic. No: {{ $co['fssai'] }}<br>
      @if ($co['phone']){{ $co['phone'] }} &nbsp;|&nbsp; @endif{{ $co['email'] }} &nbsp;|&nbsp; {{ $co['web'] }}
    </div>
  </div>

  <div class="ttl"><h1>{{ $title }}</h1><span class="stamp {{ $stampBad ? 'bad' : '' }}">{{ $stamp }}</span></div>

  <div class="grid">
    <div class="box"><h4>Invoice</h4><div><b>{{ $inv['number'] }}</b>@if (!empty($inv['pickup']))<br>Pickup no. <b style="font-size:15px">{{ $inv['pickup'] }}</b>@endif<br>{{ $dt }}@if (!$isOutlet && $inv['placed'])<br><small>Ordered: {{ \Carbon\Carbon::parse($inv['placed'])->format('d M Y, h:i A') }}</small>@endif</div></div>
    <div class="box"><h4>{{ $isOutlet ? 'Sold at' : 'Billed to' }}</h4><div>
      @if ($isOutlet)<b>Freshful outlet {{ $hubLine }}</b>@if ($inv['cashier'])<br>Cashier: {{ $inv['cashier'] }}@endif
      @else<b>{{ $inv['customer']['name'] }}</b>@if ($inv['customer']['phone'])<br>{{ $inv['customer']['phone'] }}@endif @if ($inv['customer']['address'])<br>{{ $inv['customer']['address'] }}@endif @endif</div></div>
    <div class="box"><h4>{{ $isOutlet ? 'Payment' : 'Delivery and payment' }}</h4><div>
      @if (!$isOutlet){{ $inv['delivery']['type'] }}@if ($inv['delivery']['slot']) &middot; {{ $inv['delivery']['slot'] }}@endif<br>Dispatched from {{ $hubLine }}<br>@endif
      @if ($pay['method'])Paid by: <b>{{ $pay['method'] }}</b>@else<b>Pay at the counter</b>@endif @if ($pay['ref'])<br>Ref: {{ $pay['ref'] }}@endif</div></div>
  </div>

  <table class="items">
    <thead><tr><th style="width:4%">#</th><th>Item</th><th class="n">Qty</th><th class="n">MRP</th><th class="n">Rate</th><th class="n">Amount</th></tr></thead>
    <tbody>
    @foreach ($inv['items'] as $i => $it)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td><b>{{ $it['name'] }}</b><br><small>{{ $it['pack'] }}@if (!empty($it['weight'])) &middot; weighed {{ $q($it['weight']) }} g @endif</small></td>
        <td class="n">{{ $q($it['qty']) }}</td>
        <td class="n">@if ($it['mrp'] > $it['rate'])<s>{!! $m($it['mrp']) !!}</s>@else - @endif</td>
        <td class="n">{!! $m($it['rate']) !!}</td>
        <td class="n"><b>{!! $m($it['amount']) !!}</b></td>
      </tr>
    @endforeach
    </tbody>
  </table>

  <table class="tot">
    <tr><td>Items total ({{ $inv['packs'] }} pack{{ $inv['packs'] == 1 ? '' : 's' }})</td><td class="n">{!! $m($t['base']) !!}</td></tr>
    @if ($t['discount'] > 0)<tr><td>Discount{{ $t['coupon'] ? ' (' . $t['coupon'] . ')' : '' }}</td><td class="n">- {!! $m($t['discount']) !!}</td></tr>@endif
    @if (abs($t['adjust']) >= 0.01)<tr><td>Weight / price adjustment</td><td class="n">{{ $t['adjust'] < 0 ? '-' : '+' }} {!! $m(abs($t['adjust'])) !!}</td></tr>@endif
    <tr><td>CGST</td><td class="n">{!! $m($t['cgst']) !!}</td></tr>
    <tr><td>SGST</td><td class="n">{!! $m($t['sgst']) !!}</td></tr>
    @if (!$isOutlet)<tr><td>Delivery charges</td><td class="n">{!! $m($t['delivery']) !!}</td></tr>@endif
    <tr class="net"><td>Net payable</td><td class="n">{!! $m($t['net']) !!}</td></tr>
    @if ($pay['tendered'] !== null)<tr><td>Cash received</td><td class="n">{!! $m($pay['tendered']) !!}</td></tr><tr><td>Change given</td><td class="n">{!! $m($pay['change'] ?? 0) !!}</td></tr>@endif
  </table>

  @if ($t['saved'] > 0)<div class="save">You saved {!! $m($t['saved']) !!} on this order</div>@endif

  <div class="thanks">Thank you for shopping with Freshful.</div>
  <div class="foot">
    <div>Prices are inclusive of applicable taxes. Fresh produce, meat and seafood weights may vary slightly; billing follows the final weighed quantity. For any issue with this order write to {{ $co['email'] }}@if ($co['phone']) or call {{ $co['phone'] }}@endif.</div>
    <div style="text-align:right;white-space:nowrap">{{ $co['legal'] }}<br>GSTIN {{ $co['gstin'] }}<br>FSSAI {{ $co['fssai'] }}</div>
  </div>
  <div class="tiny">This is a computer generated invoice and needs no signature.</div>
</div>
@if (!empty($autoprint))<script>window.addEventListener('load',function(){setTimeout(function(){window.print()},350)});</script>@endif
</body>
</html>
