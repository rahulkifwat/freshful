@php
  /* Freshful POS - day-end report v2 (21 Oct 2026). Same template for screen, print and the head-office email ($mail). */
  $mail = $mail ?? false;
  $hubName = $hub ? ($hub->hub . ($hub->city ? ' · ' . $hub->city : '')) : 'Hub';
  $m = fn ($n) => ((float) $n < 0 ? '-' : '') . '₹' . number_format(abs((float) $n), 2);
  $m0 = fn ($n) => ((float) $n < 0 ? '-' : '') . '₹' . number_format(abs((float) $n), 0);
  $t = $rep['types']; $inv = $rep['invoice']; $mo = $rep['money']; $out = $rep['outside'];
  $sla = $rep['sla'] ?? ['delivered' => 0, 'on_time' => 0, 'pct' => null, 'avg_min' => null, 'late' => []];
  $hourly = $rep['hourly'] ?? [];
  $products = $rep['products'] ?? [];
  $tot = fn ($k) => ($t['Express'][$k] ?? 0) + ($t['Scheduled'][$k] ?? 0);
  $cashExpected = (float) $rep['cash_to_handover'];
  $pend = collect($rep['pending']); $pendLive = $pend->where('stale', false);
  $withRiders = (float) $pendLive->sum('expected');
  $online = (float) ($mo['online_value'] ?? 0);
  $grossReceived = (float) $mo['received'] + $online + (float) $out['value'];
  $dateLabel = date('l, d M Y', strtotime($rep['date']));
  $lastClose = $rep['closes'][0] ?? null;
  // payment mix of money received today
  $mix = array_filter(['Cash' => (float) $mo['cash'], 'UPI' => (float) $mo['upi'], 'Card' => (float) $mo['card'], 'Online prepaid' => $online, 'Other (old POS)' => (float) $out['value']], fn ($v) => $v > 0);
  $mixTotal = array_sum($mix) ?: 0;
  $mixColor = ['Cash' => '#0E9F6E', 'UPI' => '#2563EB', 'Card' => '#7C3AED', 'Online prepaid' => '#B45309', 'Other (old POS)' => '#6B7280'];
  // hours with activity, shown from the first to the last active hour (at least 7 AM - 10 PM)
  $active = array_keys(array_filter($hourly, fn ($h) => $h['orders'] > 0));
  $hFrom = min(array_merge([7], $active)); $hTo = max(array_merge([22], $active));
  $hMax = max(1, max(array_map(fn ($h) => $h['orders'], $hourly ?: [['orders' => 0]])));
  $peak = $active ? array_reduce($active, fn ($c, $h) => $c === null || $hourly[$h]['orders'] > $hourly[$c]['orders'] ? $h : $c) : null;
  $hLbl = fn ($h) => date('g A', mktime($h, 0, 0));
  $pMax = max(1, max(array_map(fn ($p) => $p['value'], $products ?: [['value' => 0]])));
  $received = $tot('received');
  $funnel = [['Orders received', $received, '#2563EB'], ['Delivered to customer', $tot('delivered'), '#0E9F6E'], ['Invoices settled', $tot('settled'), '#0E7490'], ['Still pending', $tot('open'), '#B45309'], ['Rejected / cancelled', $tot('cancelled'), '#DC2626']];
  $fMax = max(1, max(array_map(fn ($f) => $f[1], $funnel)));
  if (!$mail) $I = \App\Support\AdminNew\Icons::class;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Day-end report · {{ $hub->hub ?? 'Hub' }} · {{ $rep['date'] }} · Freshful</title>
@if (!$mail)
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<style>{!! file_get_contents(resource_path('views/pos_console/assets/pos.css')) !!}</style>
@endif
<style>
  .dr{--ink:#141B2D;--ink2:#4B5563;--ink3:#6B7280;--line:#E5E9F0;--soft:#F6F8FB;--brand:#EA4A1F;--ok:#0E9F6E;--warn:#B45309;--bad:#DC2626;--info:#2563EB;
      max-width:1180px;margin:0 auto;font-family:"Plus Jakarta Sans","Segoe UI",Arial,sans-serif;color:var(--ink)}
  .dr *{box-sizing:border-box}
  .dr-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px 20px;break-inside:avoid}
  .dr-h{font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--ink3);margin:0 0 12px;display:flex;align-items:center;gap:8px}
  .dr-h .r{margin-left:auto;text-transform:none;letter-spacing:0;font-weight:600}
  .dr-head{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:flex-start;border-bottom:4px solid var(--brand);padding-bottom:16px;margin-bottom:16px}
  .dr-brand{color:var(--brand);font-weight:800;font-size:15px;letter-spacing:.02em}
  .dr-title{font-size:26px;font-weight:800;margin:2px 0}
  .dr-sub{color:var(--ink3);font-size:13.5px}
  .dr-meta{text-align:right;font-size:13px;color:var(--ink2);line-height:1.7}
  .dr-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700}
  .dr-pill.run{background:#FFF4DB;color:var(--warn)} .dr-pill.done{background:#E3F7EE;color:var(--ok)}
  .dr-grid{display:grid;gap:14px;margin-bottom:14px}
  .g5{grid-template-columns:2fr 1fr 1fr 1fr 1fr} .g2{grid-template-columns:1fr 1fr} .g32{grid-template-columns:3fr 2fr}
  .dr-kpi{background:#fff;border:1px solid var(--line);border-radius:16px;padding:16px 18px;break-inside:avoid}
  .dr-kpi small{display:block;font-size:11.5px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--ink3)}
  .dr-kpi b{display:block;font-size:24px;font-weight:800;margin-top:6px;font-variant-numeric:tabular-nums}
  .dr-kpi span{display:block;font-size:12.5px;color:var(--ink3);margin-top:3px}
  .dr-kpi.hero{border:2px solid var(--brand);background:#FFF7F3}
  .dr-kpi.hero b{font-size:34px;color:var(--brand)}
  .dr-kpi.warn b{color:var(--warn)} .dr-kpi.bad b{color:var(--bad)} .dr-kpi.ok b{color:var(--ok)}
  .dr-bar{height:10px;border-radius:6px;background:var(--soft);overflow:hidden;display:flex}
  .dr-bar i{display:block;height:100%}
  .dr-row{display:grid;grid-template-columns:170px 1fr 70px;align-items:center;gap:10px;padding:6px 0;font-size:13.5px}
  .dr-row .n{text-align:right;font-weight:800;font-variant-numeric:tabular-nums}
  .dr-legend{display:grid;grid-template-columns:1fr 1fr;gap:8px 16px;margin-top:12px;font-size:13px}
  .dr-legend div{display:flex;align-items:center;gap:8px}
  .dr-legend i{width:10px;height:10px;border-radius:3px;flex:none}
  .dr-legend b{margin-left:auto;font-variant-numeric:tabular-nums}
  .dr-hours{display:flex;align-items:flex-end;gap:6px;height:160px;padding-top:10px;border-bottom:1px solid var(--line)}
  .dr-hours div{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:4px;min-width:0}
  .dr-hours i{display:block;width:100%;max-width:34px;border-radius:6px 6px 0 0;background:#C7D2FE}
  .dr-hours i.pk{background:var(--info)}
  .dr-hours em{font-style:normal;font-size:11px;font-weight:700;color:var(--ink2)}
  .dr-hlab{display:flex;gap:6px;margin-top:6px}
  .dr-hlab span{flex:1;text-align:center;font-size:10.5px;color:var(--ink3);min-width:0;white-space:nowrap;overflow:hidden}
  .dr-big{font-size:44px;font-weight:800;line-height:1;font-variant-numeric:tabular-nums}
  .dr t, .dr table{width:100%;border-collapse:collapse;font-size:13.5px}
  .dr th{text-align:left;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--ink3);border-bottom:1px solid var(--line);padding:8px;background:var(--soft)}
  .dr td{padding:8px;border-bottom:1px solid #EEF2F7;vertical-align:middle}
  .dr .r{text-align:right;font-variant-numeric:tabular-nums} .dr .b{font-weight:800}
  .dr .tot td{font-weight:800;border-top:2px solid var(--ink);border-bottom:0}
  .dr .bad{color:var(--bad)} .dr .ok{color:var(--ok)} .dr .warn{color:var(--warn)} .dr .muted{color:var(--ink3)}
  .dr-chip{display:inline-block;padding:2px 8px;border-radius:6px;font-size:11.5px;font-weight:700;background:var(--soft);color:var(--ink2)}
  .dr-chip.ok{background:#E3F7EE;color:var(--ok)} .dr-chip.warn{background:#FFF4DB;color:var(--warn)} .dr-chip.bad{background:#FDE8E8;color:var(--bad)}
  .dr-mini{height:6px;border-radius:4px;background:var(--soft);margin-top:4px;overflow:hidden} .dr-mini i{display:block;height:100%;background:var(--brand);border-radius:4px}
  .dr-empty{color:var(--ink3);font-size:13px;padding:10px 0}
  .dr-foot{color:var(--ink3);font-size:12px;margin:10px 0 0}
  @media (max-width:980px){ .g5{grid-template-columns:1fr 1fr} .g5 .hero{grid-column:1/-1} .g2,.g32{grid-template-columns:1fr} }
  @media (max-width:560px){ .dr-row{grid-template-columns:120px 1fr 50px} .dr-meta{text-align:left} .dr-title{font-size:22px} }
  @media print{ .no-print,.pc-top{display:none !important} body{background:#fff !important} .pc-page{padding:0 !important} .dr-card,.dr-kpi{border-color:#D8DEE8} @page{size:A4;margin:10mm} .dr-grid{gap:10px} }
</style>
</head>
<body style="{{ $mail ? 'background:#F1F4F9;padding:16px;margin:0' : '' }}">
@if (!$mail)
<header class="pc-top no-print">
  <div class="pc-brand"><div class="pc-logo" aria-hidden="true">F</div><div><b>Freshful POS</b><small>{{ $hubName }}</small></div></div>
  <div class="pc-top-r">
    <a class="pc-btn" href="{{ route('posc.board') }}" style="background:var(--side-2);border-color:var(--side-line);color:#fff">Back to live board</a>
    <a class="pc-ibtn" href="{{ route('posc.logout') }}" title="Log out" aria-label="Log out">{!! $I::svg('logout', 17) !!}</a>
  </div>
</header>
<main class="pc-page">
  <div class="pc-ph no-print" style="max-width:1180px;margin-left:auto;margin-right:auto">
    <div><h1>Day-end report</h1><p>{{ $hubName }} · {{ $dateLabel }}</p></div>
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input type="date" name="date" value="{{ $rep['date'] }}" max="{{ date('Y-m-d') }}" class="pc-input" style="width:170px">
      <button class="pc-btn" type="submit">Show</button>
      <button class="pc-btn" type="button" onclick="window.print()">Print / PDF</button>
      <a class="pc-btn pri" href="#close-day">Submit to head office</a>
    </form>
  </div>
  @if (session('posc_ok'))<div class="pc-banner no-print" style="background:var(--ok-soft);color:var(--ok);margin:0 auto 12px;max-width:1180px">{{ session('posc_ok') }}</div>@endif
  @if (session('posc_err'))<div class="pc-banner is-bad no-print" style="margin:0 auto 12px;max-width:1180px">{{ session('posc_err') }}</div>@endif
@endif

<div class="dr">
  <div class="dr-card" style="margin-bottom:14px">
    <div class="dr-head" style="margin-bottom:0;border-bottom:0;padding-bottom:0">
      <div>
        <div class="dr-brand">FRESHFUL</div>
        <div class="dr-title">Day-end report</div>
        <div class="dr-sub">{{ $dateLabel }} · {{ $hubName }}</div>
      </div>
      <div class="dr-meta">
        @if ($lastClose)
          <span class="dr-pill done">Submitted to head office {{ date('h:i A', strtotime($lastClose['created_at'])) }}</span>
        @else
          <span class="dr-pill run">{{ $rep['is_today'] ? 'Day running · not submitted yet' : 'Not submitted' }}</span>
        @endif
        <div>Prepared by <b>{{ $actor['name'] ?? '' }}</b></div>
        <div>Generated {{ date('d M Y, h:i A', strtotime($rep['generated_at'])) }}</div>
      </div>
    </div>
  </div>

  {{-- Money at a glance --}}
  <div class="dr-grid g5">
    <div class="dr-kpi hero"><small>Cash to hand over</small><b>{{ $m($cashExpected) }}</b><span>cash received from riders and the counter</span></div>
    <div class="dr-kpi"><small>UPI + card</small><b>{{ $m0($mo['upi'] + $mo['card']) }}</b><span>UPI {{ $m0($mo['upi']) }} · card {{ $m0($mo['card']) }}</span></div>
    <div class="dr-kpi"><small>Online prepaid</small><b>{{ $m0($online) }}</b><span>{{ $mo['online_orders'] ?? 0 }} orders delivered</span></div>
    <div class="dr-kpi {{ $withRiders > 0 ? 'warn' : '' }}"><small>Still with riders</small><b>{{ $m0($withRiders) }}</b><span>{{ $pendLive->count() }} delivered, not settled</span></div>
    <div class="dr-kpi {{ $mo['short'] > 0 ? 'bad' : 'ok' }}"><small>Short / excess</small><b>{{ $mo['short'] > 0 ? '-' . $m0($mo['short']) : ($mo['excess'] > 0 ? '+' . $m0($mo['excess']) : '₹0') }}</b><span>{{ $mo['short'] > 0 || $mo['excess'] > 0 ? 'see money received below' : 'all settlements matched' }}</span></div>
  </div>

  <div class="dr-grid g2">
    {{-- Orders funnel --}}
    <div class="dr-card">
      <div class="dr-h">Orders <span class="r muted">{{ $inv['orders'] }} billed · {{ $m0($inv['total']) }}</span></div>
      @foreach ($funnel as [$lbl, $n, $col])
        <div class="dr-row"><span>{{ $lbl }}</span><div class="dr-bar"><i style="width:{{ round($n * 100 / $fMax) }}%;background:{{ $col }}"></i></div><span class="n">{{ $n }}</span></div>
      @endforeach
      <table style="margin-top:10px">
        <thead><tr><th></th><th class="r">Express</th><th class="r">Scheduled</th><th class="r">Total</th></tr></thead>
        <tbody>
        @foreach ([['opening', 'Opening pending'], ['received', 'Received'], ['delivered', 'Delivered'], ['settled', 'Settled'], ['open', 'Still pending'], ['cancelled', 'Rejected / cancelled']] as [$k, $lbl])
          <tr><td>{{ $lbl }}</td><td class="r">{{ $t['Express'][$k] }}</td><td class="r">{{ $t['Scheduled'][$k] }}</td><td class="r b">{{ $tot($k) }}</td></tr>
        @endforeach
        </tbody>
      </table>
      @if ($rep['stale_excluded'] > 0)<div class="dr-foot">{{ $rep['stale_excluded'] }} very old open order(s) are not counted as opening pending.</div>@endif
    </div>
    {{-- Payment mix --}}
    <div class="dr-card">
      <div class="dr-h">Money received by type <span class="r muted">{{ $m0($mixTotal) }} in total</span></div>
      @if ($mixTotal > 0)
        <div class="dr-bar" style="height:18px">@foreach ($mix as $k => $v)<i style="width:{{ round($v * 100 / $mixTotal, 2) }}%;background:{{ $mixColor[$k] }}"></i>@endforeach</div>
        <div class="dr-legend">@foreach ($mix as $k => $v)<div><i style="background:{{ $mixColor[$k] }}"></i>{{ $k }} <span class="muted">{{ round($v * 100 / $mixTotal) }}%</span><b>{{ $m0($v) }}</b></div>@endforeach</div>
      @else
        <div class="dr-empty">No money received yet on this day.</div>
      @endif
      <table style="margin-top:14px">
        <tr><td>Expected for settled orders</td><td class="r">{{ $m($mo['expected']) }}</td></tr>
        <tr><td>Received</td><td class="r">{{ $m($mo['received']) }}</td></tr>
        <tr><td>Short settlement</td><td class="r {{ $mo['short'] > 0 ? 'bad' : '' }}">{{ $m($mo['short']) }}</td></tr>
        <tr><td>Excess settlement</td><td class="r">{{ $m($mo['excess']) }}</td></tr>
        <tr class="tot"><td>Net cash to hand over</td><td class="r">{{ $m($cashExpected) }}</td></tr>
      </table>
    </div>
  </div>

  @if (!$mail)
  {{-- Orders by hour --}}
  <div class="dr-card" style="margin-bottom:14px">
    <div class="dr-h">Orders by hour <span class="r muted">{{ $peak !== null ? 'Busiest: ' . $hLbl($peak) . ' – ' . $hLbl(($peak + 1) % 24) . ' (' . $hourly[$peak]['orders'] . ' orders)' : 'No orders yet' }}</span></div>
    <div class="dr-hours">
      @for ($h = $hFrom; $h <= $hTo; $h++)
        <div title="{{ $hLbl($h) }}: {{ $hourly[$h]['orders'] }} orders, {{ $m0($hourly[$h]['value']) }}">@if ($hourly[$h]['orders'])<em>{{ $hourly[$h]['orders'] }}</em>@endif<i class="{{ $h === $peak ? 'pk' : '' }}" style="height:{{ $hourly[$h]['orders'] ? max(4, round($hourly[$h]['orders'] * 100 / $hMax)) : 0 }}%"></i></div>
      @endfor
    </div>
    <div class="dr-hlab">@for ($h = $hFrom; $h <= $hTo; $h++)<span>{{ ($h - $hFrom) % 2 === 0 ? $hLbl($h) : '' }}</span>@endfor</div>
  </div>
  @endif

  <div class="dr-grid g2">
    {{-- Delivery performance --}}
    <div class="dr-card">
      <div class="dr-h">Delivery performance</div>
      <div style="display:flex;gap:26px;align-items:flex-end;flex-wrap:wrap">
        <div><div class="dr-big {{ $sla['pct'] === null ? 'muted' : ($sla['pct'] >= 90 ? 'ok' : ($sla['pct'] >= 70 ? 'warn' : 'bad')) }}">{{ $sla['pct'] === null ? '–' : $sla['pct'] . '%' }}</div><div class="muted" style="font-size:13px;margin-top:4px">on time ({{ $sla['on_time'] }} of {{ $sla['delivered'] }})</div></div>
        <div><div class="dr-big" style="font-size:30px">{{ $sla['avg_min'] !== null ? $sla['avg_min'] . ' min' : '–' }}</div><div class="muted" style="font-size:13px;margin-top:4px">average Express delivery</div></div>
      </div>
      <div class="dr-bar" style="margin-top:14px"><i style="width:{{ (int) ($sla['pct'] ?? 0) }}%;background:var(--ok)"></i><i style="width:{{ $sla['pct'] === null ? 0 : 100 - $sla['pct'] }}%;background:#F4B4B4"></i></div>
      @if ($sla['late'])
        <table style="margin-top:12px"><thead><tr><th>Late order</th><th>Type</th><th class="r">Late by</th></tr></thead><tbody>
          @foreach ($sla['late'] as $l)<tr><td>{{ $l['order_id'] }}</td><td>{{ $l['type'] }}</td><td class="r bad">{{ $l['late_min'] >= 60 ? intdiv($l['late_min'], 60) . 'h ' . ($l['late_min'] % 60) . 'm' : $l['late_min'] . ' min' }}</td></tr>@endforeach
        </tbody></table>
      @else
        <div class="dr-empty">{{ $sla['delivered'] ? 'Every delivery met its promised time.' : 'No deliveries yet on this day.' }}</div>
      @endif
    </div>
    {{-- Top products --}}
    <div class="dr-card">
      <div class="dr-h">Top products <span class="r muted">by value, orders received</span></div>
      @forelse ($products as $i => $p)
        <div style="display:grid;grid-template-columns:24px 1fr auto;gap:10px;align-items:center;padding:7px 0;border-bottom:1px solid #EEF2F7">
          <span class="muted b">{{ $i + 1 }}</span>
          <div style="min-width:0"><b style="font-size:13.5px">{{ $p['name'] }}</b> <span class="muted" style="font-size:12px">{{ $p['pack'] }} · {{ rtrim(rtrim(number_format($p['qty'], 2, '.', ''), '0'), '.') }} sold · {{ $p['orders'] }} orders</span>
            <div class="dr-mini"><i style="width:{{ round($p['value'] * 100 / $pMax) }}%"></i></div></div>
          <b class="r">{{ $m0($p['value']) }}</b>
        </div>
      @empty
        <div class="dr-empty">No orders received on this day.</div>
      @endforelse
    </div>
  </div>

  {{-- Riders --}}
  <div class="dr-card" style="margin-bottom:14px">
    <div class="dr-h">Riders</div>
    @if ($rep['riders'])
    <table>
      <thead><tr><th>Rider</th><th class="r">Delivered</th><th class="r">On time</th><th class="r">Settled</th><th class="r">Cash</th><th class="r">UPI</th><th class="r">Card</th><th class="r">Short / excess</th><th class="r">Still with rider</th></tr></thead>
      <tbody>
      @foreach ($rep['riders'] as $r)
        <tr><td class="b">{{ $r['rider'] }}</td><td class="r">{{ $r['delivered'] }}</td>
          <td class="r">@if ($r['on_time_pct'] !== null)<span class="dr-chip {{ $r['on_time_pct'] >= 90 ? 'ok' : ($r['on_time_pct'] >= 70 ? 'warn' : 'bad') }}">{{ $r['on_time_pct'] }}%</span>@else <span class="muted">–</span> @endif</td>
          <td class="r">{{ $r['settled_orders'] }}</td><td class="r">{{ $m0($r['cash']) }}</td><td class="r">{{ $m0($r['upi']) }}</td><td class="r">{{ $m0($r['card']) }}</td>
          <td class="r {{ $r['difference'] < 0 ? 'bad' : '' }}">{{ $r['difference'] == 0 ? '–' : $m($r['difference']) }}</td>
          <td class="r {{ $r['pending_orders'] ? 'warn b' : '' }}">{{ $r['pending_orders'] ? $r['pending_orders'] . ' · ' . $m0($r['pending_amount']) : '–' }}</td></tr>
      @endforeach
      </tbody>
    </table>
    @else
    <div class="dr-empty">No rider activity on this day.</div>
    @endif
  </div>

  <div class="dr-grid g2">
    <div class="dr-card">
      <div class="dr-h">Details of invoice (orders received)</div>
      <table>
        <tr><td>Basic value ({{ $inv['orders'] }} orders)</td><td class="r">{{ $m($inv['basic']) }}</td></tr>
        <tr><td>Discount</td><td class="r">- {{ $m($inv['discount']) }}</td></tr>
        <tr><td>CGST / SGST</td><td class="r">{{ $m(0) }}</td></tr>
        <tr><td>Delivery charge</td><td class="r">{{ $m($inv['delivery']) }}</td></tr>
        <tr><td>Weight adjustments</td><td class="r">{{ $inv['adjust'] >= 0 ? '+' : '-' }} {{ $m(abs($inv['adjust'])) }}</td></tr>
        <tr class="tot"><td>Total bill</td><td class="r">{{ $m($inv['total']) }}</td></tr>
      </table>
      @if ($rep['pay_mix'])<div class="dr-foot">By payment type: @foreach ($rep['pay_mix'] as $p => $v){{ $p === 'Cash' ? 'COD' : $p }} {{ $m0($v) }}@if (!$loop->last) · @endif @endforeach</div>@endif
    </div>
    <div class="dr-card">
      <div class="dr-h">Mode of settlement</div>
      <table>
        <tr><td>A. Cash received (COD)</td><td class="r">{{ $m($mo['cash']) }}</td></tr>
        <tr><td>B. UPI received</td><td class="r">{{ $m($mo['upi']) }}</td></tr>
        <tr><td>C. Card on delivery</td><td class="r">{{ $m($mo['card']) }}</td></tr>
        <tr><td>D. Online prepaid delivered ({{ $mo['online_orders'] ?? 0 }})</td><td class="r">{{ $m($online) }}</td></tr>
        @if ($out['orders'] > 0)<tr><td>E. Settled outside the new POS ({{ $out['orders'] }})</td><td class="r">{{ $m($out['value']) }}</td></tr>@endif
        <tr class="tot"><td>Gross total</td><td class="r">{{ $m($grossReceived) }}</td></tr>
      </table>
    </div>
  </div>

  @if ($rep['handovers'])
  <div class="dr-card" style="margin-bottom:14px">
    <div class="dr-h">Money received <span class="r muted">{{ count($rep['handovers']) }} entries</span></div>
    <table>
      <thead><tr><th>Time</th><th>From</th><th class="r">Orders</th><th class="r">Expected</th><th class="r">Cash</th><th class="r">UPI</th><th class="r">Card</th><th class="r">Difference</th><th>By / note</th></tr></thead>
      <tbody>
      @foreach ($rep['handovers'] as $h)
        <tr><td>{{ date('h:i A', strtotime($h['time'])) }}</td><td>{{ $h['rider'] }}</td><td class="r">{{ $h['orders'] }}</td><td class="r">{{ $m($h['expected']) }}</td><td class="r">{{ $m($h['cash']) }}</td><td class="r">{{ $m($h['upi']) }}</td><td class="r">{{ $m($h['card']) }}</td>
          <td class="r {{ $h['difference'] < 0 ? 'bad' : '' }}">{{ $h['difference'] == 0 ? '–' : $m($h['difference']) }}</td><td class="muted">{{ $h['by'] }}{{ $h['note'] ? ' · ' . $h['note'] : '' }}{{ $h['ref'] ? ' · ref ' . $h['ref'] : '' }}</td></tr>
      @endforeach
      </tbody>
    </table>
  </div>
  @endif

  @if ($pend->count() || $rep['cancelled'])
  <div class="dr-grid g2">
    <div class="dr-card">
      <div class="dr-h">Delivered, not settled <span class="r muted">{{ $pend->count() }}</span></div>
      @forelse ($pend as $p)
        <div style="display:flex;justify-content:space-between;gap:10px;padding:7px 0;border-bottom:1px solid #EEF2F7;font-size:13.5px"><span><b>{{ $p['order_id'] }}</b>{{ $p['stale'] ? ' (old)' : '' }} · {{ $p['customer'] }}<br><span class="muted" style="font-size:12px">{{ $p['rider'] }}{{ $p['since'] ? ' · delivered ' . date('d M, h:i A', $p['since']) : '' }}</span></span><b class="r">{{ $m($p['expected']) }}</b></div>
      @empty
        <div class="dr-empty">Every delivered order is settled.</div>
      @endforelse
    </div>
    <div class="dr-card">
      <div class="dr-h">Rejected / cancelled <span class="r muted">{{ count($rep['cancelled']) }}</span></div>
      @forelse ($rep['cancelled'] as $c)
        <div style="display:flex;justify-content:space-between;gap:10px;padding:7px 0;border-bottom:1px solid #EEF2F7;font-size:13.5px"><span><b>{{ $c['order_id'] }}</b> · {{ $c['customer'] }}<br><span class="muted" style="font-size:12px">{{ $c['type'] }} · {{ $c['reason'] ?: 'no reason given' }}</span></span><b class="r">{{ $m($c['amount']) }}</b></div>
      @empty
        <div class="dr-empty">No cancellations on this day.</div>
      @endforelse
    </div>
  </div>
  @endif

  @if ($mail)
    <div class="dr-card">
      <div class="dr-h">Day close</div>
      <table>
        <tr><td>Cash expected</td><td class="r">{{ $m($cashExpected) }}</td></tr>
        <tr><td>Cash counted</td><td class="r b">{{ $m($counted ?? 0) }}</td></tr>
        <tr><td>Difference</td><td class="r {{ round(($counted ?? 0) - $cashExpected, 2) < 0 ? 'bad' : '' }}">{{ $m(($counted ?? 0) - $cashExpected) }}</td></tr>
        @if (!empty($closeNote))<tr><td>Note</td><td>{{ $closeNote }}</td></tr>@endif
      </table>
    </div>
  @endif

  @if (!$mail && $rep['closes'])
    <div class="dr-card" style="margin-bottom:14px">
      <div class="dr-h">Submitted to head office</div>
      <table>
        <thead><tr><th>When</th><th>By</th><th class="r">Cash expected</th><th class="r">Cash counted</th><th class="r">Difference</th><th>Note</th><th>Email</th></tr></thead>
        <tbody>
        @foreach ($rep['closes'] as $c)
          <tr><td>{{ date('d M, h:i A', strtotime($c['created_at'])) }}</td><td>{{ $c['by_name'] }}</td><td class="r">{{ $m($c['cash_expected']) }}</td><td class="r">{{ $m($c['cash_counted']) }}</td>
            <td class="r {{ $c['difference'] < 0 ? 'bad' : '' }}">{{ $m($c['difference']) }}</td><td>{{ $c['note'] }}</td><td class="muted">{{ $c['email_status'] }}</td></tr>
        @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@if (!$mail)
  <section class="pc-panel no-print" id="close-day" style="max-width:1180px;margin:0 auto">
    <h3 style="margin-top:0">Close the day and send to head office</h3>
    <p style="color:var(--ink-3);font-size:13px;margin:0 0 12px">Count the cash in the drawer and enter it. The full report is saved for head office{{ $headOffice ? ' and emailed to ' . $headOffice : '' }}. You can submit again if something changes; every submission is kept.</p>
    @if ($rep['is_today'] && $pendLive->count())
      <div class="pc-banner is-warn" style="margin-bottom:12px">{{ $pendLive->count() }} delivered order(s) are not settled yet ({{ $m0($withRiders) }} with riders). Collect the money before closing if you can.</div>
    @endif
    <form method="POST" action="{{ route('posc.report.close') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end" onsubmit="this.querySelector('button').disabled=true">
      @csrf
      <input type="hidden" name="date" value="{{ $rep['date'] }}">
      <label style="display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:700">Cash expected<input class="pc-input" value="{{ number_format($cashExpected, 2, '.', '') }}" disabled style="width:160px"></label>
      <label style="display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:700">Cash counted<input class="pc-input" name="cash_counted" type="number" step="0.01" min="0" required style="width:160px" value="{{ number_format($cashExpected, 2, '.', '') }}"></label>
      <label style="display:flex;flex-direction:column;gap:4px;font-size:12px;font-weight:700;flex:1;min-width:220px">Note (required if the cash does not match)<input class="pc-input" name="note" maxlength="500"></label>
      <button class="pc-btn pri lg" type="submit">Submit to head office</button>
    </form>
  </section>
</main>
@endif
</body>
</html>
