{{-- Freshful new admin console - page shell (sidebar + top bar + content) --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $pageTitle ?? 'Admin' }} &middot; Freshful Admin</title>
<link rel="icon" href="{{ url('img/Fevi.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<style>{!! file_get_contents(resource_path('views/admin_new/assets/admin.css')) !!}</style>
<script>
  try {
    var ffT = localStorage.getItem('ff-theme');
    if (ffT === 'dark' || ffT === 'light') document.documentElement.setAttribute('data-theme', ffT);
    if (localStorage.getItem('ff-rail') === '1') document.documentElement.classList.add('rail');
  } catch (e) {}
</script>
@stack('head')
</head>
<body>
@php
  $nav = \App\Support\AdminNew\Menu::build($menuKey ?? null);
  $me  = auth('admin')->user();
  $meName = trim((string) ($me->name ?? '')) !== '' ? $me->name : ($me->email ?? 'Admin');
  $meInitials = '';
  foreach (array_slice(array_values(array_filter(preg_split('/\s+/', trim((string) $meName)))), 0, 2) as $w) {
      $meInitials .= mb_strtoupper(mb_substr($w, 0, 1));
  }
@endphp
<div class="ff-app">
  @include('admin_new.partials.sidebar')
  <div class="ff-scrim" id="ffScrim"></div>
  <div class="ff-main">
    @include('admin_new.partials.topbar')
    <main class="ff-content" id="ffContent">
      @yield('content')
    </main>
  </div>
</div>
<script>{!! file_get_contents(resource_path('views/admin_new/assets/admin.js')) !!}</script>
@stack('scripts')
</body>
</html>
