@php
  $mail = $mail ?? false;
  $hubName = $hub ? ($hub->hub . ($hub->city ? ' · ' . $hub->city : '')) : 'Hub';
  $m = fn ($n) => ((float) $n < 0 ? '-' : '') . '₹' . number_format(abs((float) $n), 2);
  $t = $rep['types'];
  $inv = $rep['invoice'];
  $mo = $rep['money'];
  $out = $rep['outside'];
  $cashExpected = (float) $rep['cash_to_handover'];
  $grossReceived = (float) $mo['received'] + (float) ($mo['online_value'] ?? 0) + (float) $out['value'];
  $dateLabel = date('l, d M Y', strtotime($rep['date']));
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
<script>try { var t = localStorage.getItem('ff-theme'); if (t === 'dark' || t === 'light') document.documentElement.setAttribute('data-theme', t); } catch (e) {}</script>
@endif
<style>
  .rp{max-width:1080px;margin:0 auto;font-family:"Plus Jakarta Sans",Segoe UI,Arial,sans-serif;color:#141B2D}
  .rp-sheet{background:#fff;color:#141B2D;border:1px solid #E4E9F0;border-radius:16px;padding:22px}
  .rp h1{font-size:22px;margin:0}
  .rp h3{font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#6B7280;margin:22px 0 8px}
  .rp .muted{color:#6B7280;font-size:13px}
  .rp table{width:100%;border-collapse:collapse;font-size:13.5px}
  .rp th{text-align:left;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#6B7280;border-bottom:1px solid #E4E9F0;padding:7px 8px;background:#F8FAFC}
  .rp td{padding:7px 8px;border-bottom:1px solid #EEF2F7;vertical-align:top}
  .rp .r{text-align:right;font-variant-numeric:tabular-nums}
  .rp .b{font-weight:800}
  .rp .grid2{display:grid;grid-template-columns:1fr 1fr;gap:18px}
  .rp .head{display:flex;justify-content:space-between;gap:16px;border-bottom:3px solid #EA4A1F;padding-bottom:12px;flex-wrap:wrap}
  .rp .brand{color:#EA4A1F;font-weight:800;font-size:20px}
  .rp .tot td{font-weight:800;border-top:2px solid #141B2D}
  .rp .bad{color:#DC2626} .rp .ok{color:#0E9F6E} .rp .warn{color:#B45309}
  .rp .box{border:1px solid #E4E9F0;border-radius:12px;padding:14px}
  .rp .kp{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:14px}
  .rp .kp div{border:1px solid #E4E9F0;border-radius:12px;padding:10px 12px}
  .rp .kp small{display:block;font-size:11px;color:#6B7280;text-transform:uppercase;letter-spacing:.06em}
  .rp .kp b{font-size:20px}
  .rp .note{background:#FFF4DB;color:#7A4A06;border-radius:10px;padding:10px 12px;font-size:13px;margin-top:12px}
  @media (max-width:760px){ .rp .grid2{grid-template-columns:1fr} .rp .kp{grid-template-columns:1fr 1fr} .rp-sheet{padding:14px} }
  @media print{ .no-print,.pc-top{display:none !important} body{background:#fff !important} .rp-sheet{border:0;padding:0} @page{size:A4;margin:12mm} }
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
  <div class="pc-ph no-print">
    <div><h1>Day-end report</h1><p>{{ $hubName }} · {{ $dateLabel }}</p></div>
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input type="date" name="date" value="{{ $rep['date'] }}" max="{{ date('Y-m-d') }}" class="pc-input" style="width:170px">
      <button class="pc-btn pri" type="submit">Show</button>
      <button class="pc-btn" type="button" onclick="window.print()">Print</button>
    </form>
  </div>
  @if (session('posc_ok'))<div class="pc-banner no-print" style="background:var(--ok-soft);color:var(--ok);margin-bottom:12px">{{ session('posc_ok') }}</div>@endif
  @if (session('posc_err'))<div class="pc-banner is-bad no-print" style="margin-bottom:12px">{{ session('posc_err') }}</div>@endif
@endif

<div class="rp"><div class="rp-sheet">
  <div class="head">
    <div><div class="brand">Freshful</div><h1>Day-end report</h1><div class="muted">{{ $dateLabel }}</div></div>
    <div style="text-align:right">
      <div><b>Hub:</b> {{ $hubName }}</div>
      <div><b>Prepared by:</b> {{ $actor['name'] ?? '' }}</div>
      <div class="muted">Generated {{ date('d M Y, h:i A', strtotime($rep['generated_at'])) }}{{ $rep['is_today'] ? ' (day still running)' : '' }}</div>
    </div>
  </div>

  <div class="kp">
    <div><small>Cash to hand over</small><b>{{ $m($cashExpected) }}</b></div>
    <div><small>UPI + card received</small><b>{{ $m($mo['upi'] + $mo['card']) }}</b></div>
    <div><small>Still with riders</small><b class="{{ collect($rep['pending'])->where('stale', false)->count() ? 'warn' : '' }}">{{ $m(collect($rep['pending'])->where('stale', false)->sum('expected')) }}</b></div>
    <div><small>Short / excess</small><b class="{{ $mo['short'] > 0 ? 'bad' : '' }}">{{ $mo['short'] > 0 ? '-' . $m($mo['short']) : '' }}{{ $mo['short'] > 0 && $mo['excess'] > 0 ? ' / ' : '' }}{{ $mo['excess'] > 0 ? '+' . $m($mo['excess']) : '' }}{{ $mo['short'] == 0 && $mo['excess'] == 0 ? '₹0.00' : '' }}</b></div>
  </div>

  <div class="grid2">
    <div>
      <h3>Orders</h3>
      <table>
        <thead><tr><th></th><th class="r">Express</th><th class="r">Scheduled</th><th class="r">Total</th></tr></thead>
        <tbody>
        @foreach ([['opening', 'Opening pending (from earlier days)'], ['received', 'Orders received'], ['delivered', 'Delivered to customer'], ['settled', 'Invoices settled'], ['open', 'Still pending'], ['cancelled', 'Rejected / cancelled']] as [$k, $lbl])
          <tr><td>{{ $lbl }}</td><td class="r">{{ $t['Express'][$k] }}</td><td class="r">{{ $t['Scheduled'][$k] }}</td><td class="r b">{{ $t['Express'][$k] + $t['Scheduled'][$k] }}</td></tr>
        @endforeach
        </tbody>
      </table>
      @if ($rep['stale_excluded'] > 0)<div class="muted" style="margin-top:6px">{{ $rep['stale_excluded'] }} very old open order(s) are not counted as opening pending.</div>@endif
    </div>
    <div>
      <h3>Details of invoice (orders received)</h3>
      <table>
        <tr><td>Basic value ({{ $inv['orders'] }} orders)</td><td class="r">{{ $m($inv['basic']) }}</td></tr>
        <tr><td>Discount</td><td class="r">- {{ $m($inv['discount']) }}</td></tr>
        <tr><td>CGST / SGST</td><td class="r">{{ $m(0) }}</td></tr>
        <tr><td>Delivery charge</td><td class="r">{{ $m($inv['delivery']) }}</td></tr>
        <tr><td>Weight adjustments</td><td class="r">{{ $inv['adjust'] >= 0 ? '+' : '-' }} {{ $m(abs($inv['adjust'])) }}</td></tr>
        <tr class="tot"><td>Total bill</td><td class="r">{{ $m($inv['total']) }}</td></tr>
      </table>
      @if ($rep['pay_mix'])
        <div class="muted" style="margin-top:6px">By payment type: @foreach ($rep['pay_mix'] as $p => $v){{ $p === 'Cash' ? 'COD' : $p }} {{ $m($v) }}@if (!$loop->last) · @endif @endforeach</div>
      @endif
    </div>
  </div>

  <h3>Mode of settlement (money received at the hub today)</h3>
  <table>
    <tr><td>A. Cash received (COD)</td><td class="r">{{ $m($mo['cash']) }}</td></tr>
    <tr><td>B. UPI received</td><td class="r">{{ $m($mo['upi']) }}</td></tr>
    <tr><td>C. Card on delivery</td><td class="r">{{ $m($mo['card']) }}</td></tr>
    <tr><td>D. Online prepaid orders delivered ({{ $mo['online_orders'] ?? 0 }})</td><td class="r">{{ $m($mo['online_value'] ?? 0) }}</td></tr>
    @if ($out['orders'] > 0)
      <tr><td>E. Settled outside the new POS ({{ $out['orders'] }} orders: @foreach ($out['by_type'] as $p => $v){{ $p === 'Cash' ? 'COD' : $p }} {{ $m($v) }}@if (!$loop->last), @endif @endforeach)</td><td class="r">{{ $m($out['value']) }}</td></tr>
    @endif
    <tr class="tot"><td>Gross total</td><td class="r">{{ $m($grossReceived) }}</td></tr>
    <tr><td>Expected for settled orders</td><td class="r">{{ $m($mo['expected']) }}</td></tr>
    <tr><td>Short settlement</td><td class="r {{ $mo['short'] > 0 ? 'bad' : '' }}">{{ $m($mo['short']) }}</td></tr>
    <tr><td>Excess settlement</td><td class="r">{{ $m($mo['excess']) }}</td></tr>
    <tr class="tot"><td>Net cash to hand over to head office</td><td class="r">{{ $m($cashExpected) }}</td></tr>
  </table>

  <h3>Riders</h3>
  @if ($rep['riders'])
  <table>
    <thead><tr><th>Rider</th><th class="r">Delivered</th><th class="r">Settled</th><th class="r">Cash</th><th class="r">UPI</th><th class="r">Card</th><th class="r">Short / excess</th><th class="r">Still with rider</th></tr></thead>
    <tbody>
    @foreach ($rep['riders'] as $r)
      <tr><td>{{ $r['rider'] }}</td><td class="r">{{ $r['delivered'] }}</td><td class="r">{{ $r['settled_orders'] }}</td><td class="r">{{ $m($r['cash']) }}</td><td class="r">{{ $m($r['upi']) }}</td><td class="r">{{ $m($r['card']) }}</td>
        <td class="r {{ $r['difference'] < 0 ? 'bad' : '' }}">{{ $r['difference'] == 0 ? '-' : $m($r['difference']) }}</td>
        <td class="r {{ $r['pending_orders'] ? 'warn b' : '' }}">{{ $r['pending_orders'] ? $r['pending_orders'] . ' · ' . $m($r['pending_amount']) : '-' }}</td></tr>
    @endforeach
    </tbody>
  </table>
  @else
  <div class="muted">No rider activity on this day.</div>
  @endif

  @if ($rep['handovers'])
  <h3>Money received ({{ count($rep['handovers']) }} entries)</h3>
  <table>
    <thead><tr><th>Time</th><th>From</th><th class="r">Orders</th><th class="r">Expected</th><th class="r">Cash</th><th class="r">UPI</th><th class="r">Card</th><th class="r">Difference</th><th>By / note</th></tr></thead>
    <tbody>
    @foreach ($rep['handovers'] as $h)
      <tr><td>{{ date('h:i A', strtotime($h['time'])) }}</td><td>{{ $h['rider'] }}</td><td class="r">{{ $h['orders'] }}</td><td class="r">{{ $m($h['expected']) }}</td><td class="r">{{ $m($h['cash']) }}</td><td class="r">{{ $m($h['upi']) }}</td><td class="r">{{ $m($h['card']) }}</td>
        <td class="r {{ $h['difference'] < 0 ? 'bad' : '' }}">{{ $h['difference'] == 0 ? '-' : $m($h['difference']) }}</td><td>{{ $h['by'] }}{{ $h['note'] ? ' · ' . $h['note'] : '' }}{{ $h['ref'] ? ' · ref ' . $h['ref'] : '' }}</td></tr>
    @endforeach
    </tbody>
  </table>
  @endif

  @php $pend = collect($rep['pending']); @endphp
  @if ($pend->count())
  <h3>Delivered but not settled ({{ $pend->count() }})</h3>
  <table>
    <thead><tr><th>Order</th><th>Customer</th><th>Rider</th><th>Delivered</th><th class="r">To collect</th></tr></thead>
    <tbody>
    @foreach ($pend as $p)
      <tr><td>{{ $p['order_id'] }}{{ $p['stale'] ? ' (old)' : '' }}</td><td>{{ $p['customer'] }}</td><td>{{ $p['rider'] }}</td><td>{{ $p['since'] ? date('d M, h:i A', $p['since']) : '' }}</td><td class="r">{{ $m($p['expected']) }}</td></tr>
    @endforeach
    </tbody>
  </table>
  @endif

  @if ($rep['cancelled'])
  <h3>Rejected / cancelled ({{ count($rep['cancelled']) }})</h3>
  <table>
    <thead><tr><th>Order</th><th>Customer</th><th>Type</th><th>Reason</th><th class="r">Value</th></tr></thead>
    <tbody>
    @foreach ($rep['cancelled'] as $c)
      <tr><td>{{ $c['order_id'] }}</td><td>{{ $c['customer'] }}</td><td>{{ $c['type'] }}</td><td>{{ $c['reason'] ?: '-' }}</td><td class="r">{{ $m($c['amount']) }}</td></tr>
    @endforeach
    </tbody>
  </table>
  @endif

  @if ($mail)
    <h3>Day close</h3>
    <table>
      <tr><td>Cash expected</td><td class="r">{{ $m($cashExpected) }}</td></tr>
      <tr><td>Cash counted</td><td class="r b">{{ $m($counted ?? 0) }}</td></tr>
      <tr><td>Difference</td><td class="r {{ round(($counted ?? 0) - $cashExpected, 2) < 0 ? 'bad' : '' }}">{{ $m(($counted ?? 0) - $cashExpected) }}</td></tr>
      @if (!empty($closeNote))<tr><td>Note</td><td>{{ $closeNote }}</td></tr>@endif
    </table>
  @endif

  @if (!$mail && $rep['closes'])
    <h3>Submitted to head office</h3>
    <table>
      <thead><tr><th>When</th><th>By</th><th class="r">Cash expected</th><th class="r">Cash counted</th><th class="r">Difference</th><th>Note</th><th>Email</th></tr></thead>
      <tbody>
      @foreach ($rep['closes'] as $c)
        <tr><td>{{ date('d M, h:i A', strtotime($c['created_at'])) }}</td><td>{{ $c['by_name'] }}</td><td class="r">{{ $m($c['cash_expected']) }}</td><td class="r">{{ $m($c['cash_counted']) }}</td>
          <td class="r {{ $c['difference'] < 0 ? 'bad' : '' }}">{{ $m($c['difference']) }}</td><td>{{ $c['note'] }}</td><td>{{ $c['email_status'] }}</td></tr>
      @endforeach
      </tbody>
    </table>
  @endif
</div></div>

@if (!$mail)
  <section class="pc-panel no-print" style="max-width:1080px;margin:16px auto 0">
    <h3 style="margin-top:0">Close the day and send to head office</h3>
    <p style="color:var(--ink-3);font-size:13px;margin:0 0 12px">Count the cash in the drawer and enter it. The full report is saved for head office{{ $headOffice ? ' and emailed to ' . $headOffice : '' }}. You can submit again if something changes; every submission is kept.</p>
    @if ($rep['is_today'] && collect($rep['pending'])->where('stale', false)->count())
      <div class="pc-banner is-warn" style="margin-bottom:12px">{{ collect($rep['pending'])->where('stale', false)->count() }} delivered order(s) are not settled yet. Collect the money from riders before closing if you can.</div>
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
