@php
  $I = \App\Support\AdminNew\Icons::class;
  $hubName = $hub ? ($hub->hub . ($hub->city ? ' · ' . $hub->city : '')) : 'Hub';
  $t = $rep['totals'];
  $m = fn ($n) => '₹' . number_format((float) $n, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Day-end report · {{ $rep['date'] }} · Freshful POS</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<style>{!! file_get_contents(resource_path('views/pos_console/assets/pos.css')) !!}
@media print{ .pc-top,.no-print{display:none !important} body{background:#fff} .pc-panel,.pc-kpi{box-shadow:none} }</style>
<script>try { var t = localStorage.getItem('ff-theme'); if (t === 'dark' || t === 'light') document.documentElement.setAttribute('data-theme', t); } catch (e) {}</script>
</head>
<body>
<header class="pc-top">
  <div class="pc-brand"><div class="pc-logo" aria-hidden="true">F</div><div><b>Freshful POS</b><small>{{ $hubName }}</small></div></div>
  <div class="pc-top-r">
    <a class="pc-btn" href="{{ route('posc.board') }}" style="background:var(--side-2);border-color:var(--side-line);color:#fff">Back to live board</a>
    <a class="pc-ibtn" href="{{ route('posc.logout') }}" title="Log out" aria-label="Log out">{!! $I::svg('logout', 17) !!}</a>
  </div>
</header>
<main class="pc-page">
  <div class="pc-ph">
    <div><h1>Day-end report</h1><p>{{ $hubName }} · {{ date('l, d M Y', strtotime($rep['date'])) }}</p></div>
    <form method="GET" class="no-print" style="display:flex;gap:8px;align-items:center">
      <input type="date" name="date" value="{{ $rep['date'] }}" max="{{ date('Y-m-d') }}" class="pc-input" style="width:180px">
      <button class="pc-btn pri" type="submit">Show</button>
      <button class="pc-btn" type="button" onclick="window.print()">Print</button>
    </form>
  </div>

  <section class="pc-kpis">
    <div class="pc-kpi"><div class="l">Carried in</div><div class="v">{{ $rep['carried_in'] }}</div><div class="s">open from earlier days</div></div>
    <div class="pc-kpi"><div class="l">Received</div><div class="v">{{ $t['received'] }}</div><div class="s">orders placed this day</div></div>
    <div class="pc-kpi"><div class="l">Delivered</div><div class="v">{{ $t['delivered'] }}</div><div class="s">{{ $m($t['delivered_value']) }}</div></div>
    <div class="pc-kpi {{ $t['open'] ? 'is-warn' : '' }}"><div class="l">Still open</div><div class="v">{{ $t['open'] }}</div><div class="s">not delivered yet</div></div>
    <div class="pc-kpi {{ $t['cancelled'] ? 'is-bad' : '' }}"><div class="l">Cancelled</div><div class="v">{{ $t['cancelled'] }}</div><div class="s">rejected or cancelled</div></div>
    <div class="pc-kpi"><div class="l">Settled</div><div class="v">{{ $m($rep['collected']) }}</div><div class="s">{{ $rep['collected_count'] }} orders marked paid</div></div>
  </section>

  <div class="pc-rep" style="grid-template-columns:repeat(2,minmax(0,1fr))">
    <section class="pc-panel">
      <h3>By delivery type</h3>
      <table class="pc-table">
        <thead><tr><th>Type</th><th class="r">Received</th><th class="r">Delivered</th><th class="r">Open</th><th class="r">Cancelled</th></tr></thead>
        <tbody>
        @foreach ($rep['types'] as $type => $v)
          <tr><td><b>{{ $type }}</b></td><td class="r">{{ $v['received'] ?? 0 }}</td><td class="r">{{ $v['delivered'] ?? 0 }}</td><td class="r">{{ $v['open'] ?? 0 }}</td><td class="r">{{ $v['cancelled'] ?? 0 }}</td></tr>
        @endforeach
        </tbody>
      </table>
    </section>
    <section class="pc-panel">
      <h3>Order value by payment type</h3>
      <table class="pc-table">
        <thead><tr><th>Payment</th><th class="r">Value (excl. cancelled)</th></tr></thead>
        <tbody>
        @forelse ($rep['by_payment'] as $p => $v)
          <tr><td><b>{{ $p === 'Cash' ? 'Cash on delivery' : $p }}</b></td><td class="r">{{ $m($v) }}</td></tr>
        @empty
          <tr><td colspan="2" style="color:var(--ink-3)">No orders on this day.</td></tr>
        @endforelse
        <tr><td><b>Total</b></td><td class="r"><b>{{ $m($t['value']) }}</b></td></tr>
        </tbody>
      </table>
    </section>
  </div>
  <p style="color:var(--ink-3);font-size:12px;margin:0">Printed by {{ $actor['name'] ?? '' }} at {{ date('d M Y, h:i A') }}. "Carried in" counts orders placed before this day that were still open when the day began.</p>
</main>
</body>
</html>
