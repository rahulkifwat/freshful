@php
  /* Freshful country console shell (22 Oct 2026). All screens are drawn by cc.js from the JSON endpoints. */
  $ic = [
    'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'pulse' => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
    'trophy' => '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/>',
    'receipt' => '<path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
    'calendar' => '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
    'close' => '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>',
    'package' => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/>',
    'store' => '<path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v2a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0z"/><path d="M5 13v7h14v-7"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'truck' => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
    'history' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
    'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
    'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    'transfer' => '<path d="M4 8h13l-3-3M20 16H7l3 3"/>',
    'cash' => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v.01M18 15v.01"/>',
    'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3 3 0 0 1 0 5.6M17.5 14a5 5 0 0 1 3 5"/>',
    'bike' => '<circle cx="6" cy="17" r="3"/><circle cx="18" cy="17" r="3"/><path d="M6 17l4-8h5l3 8M10 9l-2-3H6M15 9l1-3h2"/>',
    'alert' => '<path d="M12 3l9.5 17h-19z"/><path d="M12 10v4M12 17.5h0"/>',
    'filechart' => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 17v-3M12 17v-6M15 17v-2"/>',
    'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>',
    'old' => '<path d="M4 4h16v16H4z"/><path d="M4 9h16M9 9v11"/>',
    'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    'refresh' => '<path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/>',
    'moon' => '<path d="M20 14.5A8 8 0 1 1 9.5 4 6.5 6.5 0 0 0 20 14.5z"/>',
    'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5L19 19M5 19l1.5-1.5M17.5 6.5L19 5"/>',
    'logout' => '<path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/>',
    'scale' => '<path d="M12 3v18M5 7h14M5 7l-3 7a3 3 0 0 0 6 0zM19 7l-3 7a3 3 0 0 0 6 0z"/>',
  ];
  $svg = fn ($n, $s = 18) => '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($ic[$n] ?? $ic['grid']) . '</svg>';
  $nav = [
    'Command' => [['overview', 'Command centre', 'grid'], ['live', 'Live board', 'pulse'], ['league', 'State / city / hub ranking', 'trophy']],
    'Orders' => [['orders', 'All orders', 'receipt'], ['sched', 'Scheduled orders', 'calendar'], ['cancels', 'Cancellations', 'close']],
    'Inventory management' => [['sheet', 'Hub inventory sheet', 'grid'], ['stock', 'Stock by product', 'package'], ['stock-hubs', 'Stock by hub', 'store'], ['danger', 'Danger & expired stock', 'clock'], ['ledger', 'Stock ledger', 'history']],
    'Communicator' => [['comm-txn', 'Hub inward / outward transactions', 'history'], ['inward', 'Accept inward stocks', 'truck'], ['request', 'Request outward stocks', 'transfer'], ['ihsend', 'InterHub orders (send stock)', 'truck'], ['transfers', 'InterHub moments view', 'pulse']],
    'Wastage' => [['writeoff', 'Write off / spoilage', 'trash'], ['wastage', 'Wastage report', 'chart']],
    'Money' => [['cash', 'Cash at hubs', 'cash']],
    'Customer management' => [['customers', 'All customers', 'users'], ['wallets', 'Wallet transactions', 'cash']],
    'Delivery boy' => [['rider-add', 'Add delivery boy', 'bike'], ['riders', 'All delivery boys', 'bike'], ['rider-perf', 'Performance & incentives', 'trophy']],
    'Service' => [['grievances', 'Grievances', 'alert']],
    'Reports' => [['reports', 'Sales report', 'filechart'], ['targets', 'Targets', 'target']],
  ];
  $role = (string) ($actor['role'] ?? 'country'); $prefix = (string) ($actor['prefix'] ?? 'country_manager'); $names = (string) ($actor['names'] ?? 'ccc.');
  $roleLabel = (string) ($actor['role_label'] ?? 'Country manager');
  $brand = ['country' => ['Freshful Country', 'Pan-India command centre'], 'area' => ['Freshful Area', 'Your hubs command centre'], 'operation' => ['Freshful Operations', 'Operations command centre']][$role] ?? ['Freshful Country', 'Pan-India command centre'];
  $name = (string) ($actor['name'] ?? $roleLabel);
  $initials = '';
  foreach (array_slice(array_values(array_filter(preg_split('/\s+/', trim($name)))), 0, 2) as $w) $initials .= mb_strtoupper(mb_substr($w, 0, 1));
  $logout = \Illuminate\Support\Facades\Route::has($prefix . '.logout') ? route($prefix . '.logout') : url($prefix . '/logout');
  $icons = []; foreach ($ic as $k => $v) $icons[$k] = $svg($k, 16);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $brand[0] }} · command centre</title>
<link rel="icon" href="{{ asset('assets/image/Fevi.png') }}">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<style>{!! file_get_contents(resource_path('views/country_console/assets/cc.css')) !!}</style>
<script>try { var t = localStorage.getItem('ff-theme'); if (t === 'dark' || t === 'light') document.documentElement.setAttribute('data-theme', t); } catch (e) {}</script>
</head>
<body>
<div class="cc-shell">
  <aside class="cc-side" id="ccSide">
    <div class="cc-brand"><div class="cc-logo">F</div><div><b>{{ $brand[0] }}</b><small>{{ $brand[1] }}</small></div></div>
    <nav class="cc-nav" aria-label="Country console">
      @foreach ($nav as $group => $items)
        <div class="cc-sep">{{ $group }}</div>
        @foreach ($items as [$key, $label, $icon])
          <a href="#{{ $key }}" data-view="{{ $key }}">{!! $svg($icon) !!}<span>{{ $label }}</span><em class="cc-badge" id="nb-{{ $key }}" hidden></em></a>
        @endforeach
      @endforeach
      <div class="cc-sep">Other</div>
      <a href="{{ route($names . 'classic') }}">{!! $svg('old') !!}<span>Old {{ strtolower($roleLabel) }} panel</span></a>
    </nav>
    <div class="cc-me"><span class="cc-av">{{ $initials !== '' ? $initials : 'C' }}</span><div><b>{{ $name }}</b><small>{{ $roleLabel }}</small></div>
      <a class="cc-ib" href="{{ $logout }}" title="Log out" aria-label="Log out">{!! $svg('logout', 17) !!}</a></div>
  </aside>
  <div class="cc-main">
    <header class="cc-top">
      <button type="button" class="cc-ib cc-menu" id="ccMenu" aria-label="Menu">{!! $svg('menu') !!}</button>
      <div class="cc-title"><h1 id="ccTitle">Command centre</h1><p id="ccSub">{{ date('l, d M Y') }}</p></div>
      <span class="cc-live" id="ccLive"><i></i><span id="ccLiveText">Loading…</span></span>
      <button type="button" class="cc-ib" id="ccRefresh" title="Refresh" aria-label="Refresh">{!! $svg('refresh', 17) !!}</button>
      <button type="button" class="cc-ib" id="ccTheme" title="Light / dark" aria-label="Light or dark"><span class="cc-moon">{!! $svg('moon', 17) !!}</span><span class="cc-sun">{!! $svg('sun', 17) !!}</span></button>
    </header>
    <div class="cc-filters" id="ccFilters" role="search" aria-label="Filters">
      <label id="fStateWrap">State<select class="cc-sel" id="fState"><option value="">All states</option></select></label>
      <label>City<select class="cc-sel" id="fCity"><option value="">All cities</option></select></label>
      <label>Hub<select class="cc-sel" id="fHub"><option value="">All hubs</option></select></label>
      <label id="fTypeWrap">Hub type<select class="cc-sel" id="fType"><option value="">Own + franchise</option></select></label>
      <label>Status<select class="cc-sel" id="fStatus"><option value="all">All hubs</option><option value="active">Active hubs</option><option value="inactive">Inactive hubs</option></select></label>
      <div class="cc-dates" id="ccDates">
        <label>Dates<select class="cc-sel" id="fPreset">
          <option value="today">Today</option><option value="yesterday">Yesterday</option><option value="7">Last 7 days</option><option value="30">Last 30 days</option>
          <option value="month">This month</option><option value="lastmonth">Last month</option><option value="90">Last 90 days</option><option value="custom">Custom…</option></select></label>
        <label id="fCustom" hidden>From / to<span style="display:flex;gap:4px"><input type="date" class="cc-in" id="fFrom"><input type="date" class="cc-in" id="fTo"></span></label>
      </div>
      <label>Units<select class="cc-sel" id="fUnits"><option value="w">kg / pcs</option><option value="p">Packs</option></select></label>
      <button type="button" class="cc-clear" id="fClear">Clear</button>
      <div class="cc-scope" id="ccScope"></div>
    </div>
    <main class="cc-view" id="ccView" aria-live="polite"></main>
  </div>
</div>
<div class="cc-over" id="ccOver"></div>
<aside class="cc-drawer" id="ccDrawer" aria-modal="true" role="dialog"><header><h3 id="ccDTitle"></h3><button type="button" class="cc-ib" id="ccDClose" aria-label="Close">{!! $svg('close', 17) !!}</button></header><div id="ccDBody"></div></aside>
<section class="cc-modal" id="ccModal" aria-modal="true" role="dialog"><header><h3 id="ccMTitle"></h3><button type="button" class="cc-ib" id="ccMClose" aria-label="Close">{!! $svg('close', 17) !!}</button></header><div id="ccMBody"></div><footer id="ccMFoot"></footer></section>
<div class="cc-toasts" id="ccToasts" aria-live="assertive"></div>
<div class="cc-tip" id="ccTip"></div>
<script>
window.CCC = {!! json_encode([
  'base' => url($prefix . '/console/api'), 'csrf' => csrf_token(), 'login' => url($prefix . '/login'), 'role' => $role, 'roleLabel' => $roleLabel,
  'meta' => $meta, 'reasons' => $reasons, 'groups' => $groups, 'moveTypes' => $moveTypes, 'tStatus' => $tStatus, 'targetsOn' => $targetsOn,
  'actor' => $name, 'today' => date('Y-m-d'), 'icons' => $icons, 'cashLimit' => (float) config('hub_console.cash_limit', 20000),
  'wasteLimit' => (float) config('country_console.wastage_limit_pct', 3), 'planTime' => (string) config('hub_console.danger_plan_time', '11:00'),
], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
</script>
<script>{!! file_get_contents(resource_path('views/country_console/assets/cc.js')) !!}</script>
</body>
</html>
