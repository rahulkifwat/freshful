@php
  $I = \App\Support\AdminNew\Icons::class;
  $hubName = $hub ? ($hub->hub . (!empty($hub->city) ? ' · ' . $hub->city : '')) : 'Hub';
  $initials = '';
  foreach (array_slice(array_values(array_filter(preg_split('/\s+/', trim((string) ($actor['name'] ?? ''))))), 0, 2) as $w) { $initials .= mb_strtoupper(mb_substr($w, 0, 1)); }
  // Same sections as the old hub panel (owner's request, 22 Oct 2026), plus the new tools.
  $nav = [
    '' => [['overview', 'Business dashboard', 'grid']],
    'Inventory management' => [['inward', 'Pending inward', 'truck'], ['inventory', 'Hub inventory', 'package'], ['danger', 'Danger stock', 'clock'],
      ['dplan', 'Transfer danger stock', 'transfer'], ['locked', 'Locked inventory', 'shield'], ['ledger', 'Stock ledger', 'history'], ['prep', 'Prep list', 'scale']],
    'Communicator' => [['txn', 'Hub inward / outward transactions', 'layers'], ['accept', 'Accept inward stocks', 'package'], ['request', 'Request outward stocks', 'undo'],
      ['ihorder', 'InterHub orders', 'truck'], ['transfers', 'InterHub moments view', 'map']],
    'Orders' => [['orders-today', 'Today\'s orders', 'receipt'], ['orders-cancel', 'Today\'s cancelled orders', 'close'], ['orders-sched', 'Scheduled orders', 'calendar'], ['orders', 'All orders', 'filter']],
    'Cash deposit' => [['cash-add', 'Add deposit receipt', 'card'], ['cash', 'View cash deposits', 'cash']],
    'Customer management' => [['customers', 'All customers', 'users'], ['wallets', 'Wallet transactions', 'wallet']],
    'Delivery boy' => [['riders-add', 'Add delivery boy', 'user'], ['riders', 'All delivery boys', 'bike']],
  ];
  $classic = [
    ['hub.all_customers', 'Customers', 'users'], ['hub.delivery_boy', 'Delivery boys', 'bike'], ['hub.wallet_history', 'Wallet history', 'wallet'],
    ['hub.scheduled_orders', 'Scheduled orders (old)', 'calendar'], ['hub.cash_deposit', 'Cash deposit (old)', 'cash'],
  ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Hub · {{ $hubName }} · Freshful</title>
<link rel="icon" href="{{ url('img/Fevi.png') }}">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<style>{!! file_get_contents(resource_path('views/hub_console/assets/hub.css')) !!}</style>
<script>try { var t = localStorage.getItem('ff-theme'); if (t === 'dark' || t === 'light') document.documentElement.setAttribute('data-theme', t); } catch (e) {}</script>
</head>
<body>
<div class="hc-shell">
  <aside class="hc-side" id="hcSide">
    <div class="hc-brand"><div class="hc-logo">F</div><div><b>Freshful Hub</b><small>{{ $hubName }}</small></div></div>
    <nav class="hc-nav" aria-label="Hub">
      @foreach ($nav as $group => $items)
        @if ($group !== '')<div class="hc-sep">{{ $group }}</div>@endif
        @foreach ($items as [$key, $label, $icon])
          <a href="#{{ $key }}" data-view="{{ $key }}">{!! $I::svg($icon, 18) !!}<span>{{ $label }}</span><em class="hc-badge" id="nb-{{ $key }}" hidden></em></a>
        @endforeach
      @endforeach
      <div class="hc-sep">Shortcuts</div>
      <a href="{{ route('posc.board') }}" target="_blank" rel="noopener">{!! $I::svg('store', 18) !!}<span>POS console</span>{!! $I::svg('external', 14) !!}</a>
      <a href="{{ route('posc.report') }}" target="_blank" rel="noopener">{!! $I::svg('filechart', 18) !!}<span>Day-end report</span>{!! $I::svg('external', 14) !!}</a>
      <details class="hc-old"><summary class="hc-sep">Old pages</summary>
      @foreach ($classic as [$route, $label, $icon])
        @if (\Illuminate\Support\Facades\Route::has($route))<a href="{{ route($route) }}">{!! $I::svg($icon, 18) !!}<span>{{ $label }}</span></a>@endif
      @endforeach
      </details>
    </nav>
    <div class="hc-me"><span class="hc-av">{{ $initials !== '' ? $initials : 'H' }}</span><div><b>{{ $actor['name'] ?? 'Hub manager' }}</b><small>Hub manager</small></div>
      <a class="hc-ib" href="{{ route('hub.logout') }}" title="Log out" aria-label="Log out">{!! $I::svg('logout', 17) !!}</a></div>
  </aside>
  <div class="hc-main">
    <header class="hc-top">
      <button type="button" class="hc-ib hc-menu" id="hcMenu" aria-label="Menu">{!! $I::svg('menu', 18) !!}</button>
      <div class="hc-title"><h1 id="hcTitle">Command center</h1><p id="hcSub">{{ $hubName }} · {{ date('l, d M Y') }}</p></div>
      <span class="hc-live" id="hcLive"><i></i><span id="hcLiveText">Loading…</span></span>
      <button type="button" class="hc-ib" id="hcRefresh" title="Refresh" aria-label="Refresh">{!! $I::svg('history', 17) !!}</button>
      <button type="button" class="hc-ib" id="hcTheme" title="Light / dark" aria-label="Light or dark"><span class="hc-moon">{!! $I::svg('moon', 17) !!}</span><span class="hc-sun">{!! $I::svg('sun', 17) !!}</span></button>
    </header>
    <main class="hc-view" id="hcView" aria-live="polite"></main>
  </div>
</div>
<div class="hc-toasts" id="hcToasts" aria-live="assertive"></div>
<script>
window.HUBC = {!! json_encode([
  'overview' => route('hubc.overview'), 'prep' => route('hubc.prep'), 'inventory' => route('hubc.inventory'), 'orders' => route('hubc.orders'),
  'inward' => route('hubc.inward'), 'count' => route('hubc.count'), 'pos' => route('posc.board'), 'report' => route('posc.report'),
  'login' => route('hub.login'), 'csrf' => csrf_token(), 'hub' => $hubName, 'hubId' => (int) ($hub->id ?? 0),
  'locked' => route('hubc.locked'), 'ledger' => route('hubc.ledger'), 'inwardMany' => route('hubc.inward.many'), 'transfers' => route('hubc.transfers'), 'tSend' => route('hubc.transfer.send'), 'tRequest' => route('hubc.transfer.request'), 'tBase' => url('hub/console/api/transfers'),
  'cash' => route('hubc.cash'), 'cashAdd' => route('hubc.cash.add'), 'cashBase' => url('hub/console/api/cash'), 'receiptBase' => url('hub/console/cash/receipt'),
  'riders' => route('hubc.riders'), 'riderSave' => route('hubc.rider.save'), 'riderBase' => url('hub/console/api/riders'), 'customers' => route('hubc.customers'), 'grievances' => route('hubc.grievances'), 'danger' => route('hubc.danger'), 'dangerPlan' => route('hubc.danger.plan'), 'writeOff' => route('hubc.writeoff'), 'txn' => route('hubc.transactions'), 'wallets' => route('hubc.wallets'), 'planTime' => (string) config('hub_console.danger_plan_time', '11:00'), 'customerBase' => url('hub/console/api/customers'),
  'actor' => (string) ($actor['name'] ?? ''),
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
</script>
<script>{!! file_get_contents(resource_path('views/hub_console/assets/hub.js')) !!}</script>
</body>
</html>
