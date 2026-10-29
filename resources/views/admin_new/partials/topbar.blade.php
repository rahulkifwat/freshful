{{-- Freshful new admin - top bar --}}
@php $I = \App\Support\AdminNew\Icons::class; @endphp
<header class="ff-top">
  <button type="button" class="ff-icon-btn" id="ffMenuBtn" aria-label="Show or hide the menu" aria-controls="ffSide" title="Show or hide the menu">
    {!! $I::svg('sidebar', 18) !!}
  </button>
  <div class="ff-crumb">
    <span>{{ $nav['current']['group'] }}</span>
    <i aria-hidden="true">/</i>
    <b>{{ $nav['current']['label'] }}</b>
  </div>
  <div class="ff-top-r">
    <button type="button" class="ff-search-btn" id="ffSearchBtn" title="Find a page (Ctrl+K)">
      {!! $I::svg('search', 15) !!}<span>Find a page</span><kbd>Ctrl K</kbd>
    </button>
    <button type="button" class="ff-icon-btn" id="ffThemeBtn" aria-label="Switch between light and dark" title="Light / dark">
      <span class="ff-moon">{!! $I::svg('moon', 17) !!}</span><span class="ff-sun">{!! $I::svg('sun', 17) !!}</span>
    </button>
    <a class="ff-btn ff-hide-sm" href="{{ url('admin/dashboard') }}">Classic admin</a>
    <div class="ff-me">
      <span class="ff-avatar" aria-hidden="true">{{ $meInitials !== '' ? $meInitials : 'A' }}</span>
      <span class="ff-me-t"><b>{{ $meName }}</b><small>Administrator</small></span>
    </div>
    <a class="ff-icon-btn" href="{{ route('admin.logout') }}" aria-label="Log out" title="Log out">{!! $I::svg('logout', 17) !!}</a>
  </div>
</header>
