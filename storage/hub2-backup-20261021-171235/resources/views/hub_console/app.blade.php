@php
  $I = \App\Support\AdminNew\Icons::class;
  $hubName = $hub ? ($hub->hub . (!empty($hub->city) ? ' · ' . $hub->city : '')) : 'Hub';
  $initials = '';
  foreach (array_slice(array_values(array_filter(preg_split('/\s+/', trim((string) ($actor['name'] ?? ''))))), 0, 2) as $w) { $initials .= mb_strtoupper(mb_substr($w, 0, 1)); }
  $nav = [
    ['overview', 'Command center', 'grid'], ['prep', 'Prep list', 'scale'], ['inventory', 'Inventory', 'package'], ['inward', 'Receive stock', 'truck'], ['orders', 'Orders', 'receipt'],
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
      @foreach ($nav as [$key, $label, $icon])
        <a href="#{{ $key }}" data-view="{{ $key }}">{!! $I::svg($icon, 18) !!}<span>{{ $label }}</span><em class="hc-badge" id="nb-{{ $key }}" hidden></em></a>
      @endforeach
      <div class="hc-sep">Operations</div>
      <a href="{{ route('posc.board') }}" target="_blank" rel="noopener">{!! $I::svg('store', 18) !!}<span>POS console</span>{!! $I::svg('external', 14) !!}</a>
      <a href="{{ route('posc.report') }}" target="_blank" rel="noopener">{!! $I::svg('filechart', 18) !!}<span>Day-end report</span>{!! $I::svg('external', 14) !!}</a>
      <div class="hc-sep">Classic pages</div>
      @foreach ($classic as [$route, $label, $icon])
        @if (\Illuminate\Support\Facades\Route::has($route))<a href="{{ route($route) }}">{!! $I::svg($icon, 18) !!}<span>{{ $label }}</span></a>@endif
      @endforeach
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
  'login' => route('hub.login'), 'csrf' => csrf_token(), 'hub' => $hubName,
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
</script>
<script>{!! file_get_contents(resource_path('views/hub_console/assets/hub.js')) !!}</script>
</body>
</html>
