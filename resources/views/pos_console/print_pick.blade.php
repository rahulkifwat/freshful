<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Packing list {{ $o['order_id'] }}</title>
<style>
  @page { size: 80mm auto; margin: 3mm; }
  body { margin: 0 auto; width: 74mm; font-family: "Courier New", ui-monospace, monospace; font-size: 13px; color: #000; background: #fff; }
  h1 { font-size: 20px; margin: 0 0 2px; text-align: center; }
  .c { text-align: center; }
  hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
  .it { display: flex; gap: 8px; padding: 6px 0; border-bottom: 1px dotted #000; }
  .box { width: 16px; height: 16px; border: 2px solid #000; flex: none; margin-top: 1px; }
  .q { margin-left: auto; font-weight: 700; font-size: 15px; white-space: nowrap; }
  .tools { text-align: center; margin: 10px 0; }
  @media print { .tools { display: none; } }
</style>
</head>
<body>
<div class="tools"><button onclick="window.print()">Print</button></div>
<h1>PACK {{ $o['order_id'] }}</h1>
<div class="c">{{ $o['delivery_type'] }}{{ $o['slot'] ? ' · ' . $o['slot'] : '' }}</div>
<div class="c">Deliver by {{ $o['due_at'] ? date('d/m h:i A', $o['due_at']) : '-' }}</div>
<hr>
@foreach ($o['lines'] as $it)
  <div class="it"><span class="box"></span><div style="flex:1"><b>{{ $it['name'] }}</b><br>{{ $it['unit'] }} × {{ rtrim(rtrim(number_format($it['qty'], 3, '.', ''), '0'), '.') }}
    @if ($it['weighable'])<br>Ordered: <b>{{ round($it['expected_g']) }} g</b> &nbsp; Actual: {{ $it['actual_g'] !== null ? round($it['actual_g']) . ' g' : '________ g' }}@else<br>Per piece
    @endif
  </div><span class="q">× {{ rtrim(rtrim(number_format($it['qty'], 3, '.', ''), '0'), '.') }}</span></div>
@endforeach
<hr>
<div>{{ $o['customer'] }} · {{ $o['phone'] }}</div>
<div>{{ $o['address'] }}</div>
<hr>
<div>Packed by: ____________</div>
<div style="margin-top:8px">Checked by: ___________</div>
@if (request('auto'))<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 250); });</script>@endif
</body>
</html>
