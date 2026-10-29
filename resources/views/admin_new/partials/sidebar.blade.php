{{-- Freshful new admin - sidebar. Menu entries come from App\Support\AdminNew\Menu --}}
@php $I = \App\Support\AdminNew\Icons::class; @endphp
<aside class="ff-side" id="ffSide" aria-label="Main menu">
  <div class="ff-brand">
    <div class="ff-logo" aria-hidden="true">F</div>
    <div class="ff-brand-t"><b>Freshful</b><small>Admin console</small></div>
    <button type="button" class="ff-side-x" id="ffSideClose" aria-label="Close menu">{!! $I::svg('close', 18) !!}</button>
  </div>

  <label class="ff-find" for="ffFind">
    {!! $I::svg('search', 15) !!}
    <input id="ffFind" type="search" placeholder="Find a page" autocomplete="off" aria-label="Find a page in the menu">
    <kbd>/</kbd>
  </label>

  <nav class="ff-nav" id="ffNav">
    @foreach ($nav['groups'] as $g)
      <section class="ff-grp{{ $g['active'] ? ' is-active' : '' }}" data-group="{{ $g['key'] }}" style="--c: {{ $g['color'] }}">
        <button type="button" class="ff-grp-h" aria-expanded="true">
          <span>{{ $g['title'] }}</span>{!! $I::svg('chevron', 14) !!}
        </button>
        <ul class="ff-list">
          @foreach ($g['items'] as $it)
            <li class="ff-li" data-label="{{ mb_strtolower($it['label'] . ' ' . $g['title']) }}">
              @if ($it['state'] === 'parent')
                <button type="button" class="ff-item ff-parent" aria-expanded="false" data-tip="{{ $it['label'] }}">
                  <span class="ff-tile">{!! $I::svg($it['icon']) !!}</span>
                  <span class="ff-lbl">{{ $it['label'] }}</span>
                  <span class="ff-chev">{!! $I::svg('chevron', 14) !!}</span>
                </button>
                <ul class="ff-sub">
                  @foreach ($it['children'] as $c)
                    <li class="ff-li" data-label="{{ mb_strtolower($c['label'] . ' ' . $it['label']) }}">
                      <a class="ff-subitem" href="{{ $c['href'] }}" title="Opens the classic admin page">
                        <span>{{ $c['label'] }}</span><span class="ff-ext">{!! $I::svg('external', 12) !!}</span>
                      </a>
                    </li>
                  @endforeach
                </ul>
              @elseif ($it['state'] === 'soon')
                <span class="ff-item is-soon" aria-disabled="true" data-tip="{{ $it['label'] }} &middot; coming soon">
                  <span class="ff-tile">{!! $I::svg($it['icon']) !!}</span>
                  <span class="ff-lbl">{{ $it['label'] }}</span>
                  <span class="ff-soon">Soon</span>
                </span>
              @else
                <a class="ff-item{{ $it['active'] ? ' is-on' : '' }}{{ $it['state'] === 'classic' ? ' is-classic' : '' }}"
                   href="{{ $it['href'] }}" data-tip="{{ $it['label'] }}"
                   @if ($it['active']) aria-current="page" @endif
                   @if ($it['state'] === 'classic') title="Opens the classic admin page" @endif>
                  <span class="ff-tile">{!! $I::svg($it['icon']) !!}</span>
                  <span class="ff-lbl">{{ $it['label'] }}</span>
                  @if ($it['count'])<span class="ff-cnt">{{ $it['count'] > 999 ? '999+' : $it['count'] }}</span>@endif
                  @if ($it['state'] === 'classic')<span class="ff-ext">{!! $I::svg('external', 12) !!}</span>@endif
                </a>
              @endif
            </li>
          @endforeach
        </ul>
      </section>
    @endforeach
    <p class="ff-nores" id="ffNoRes" hidden>No page matches.</p>
  </nav>

  <div class="ff-side-foot">
    <div class="ff-legend">
      <span>{!! $I::svg('external', 12) !!} Classic page</span>
      <span><i class="ff-soon">Soon</i> Later phase</span>
    </div>
    <a class="ff-classic" href="{{ url('admin/dashboard') }}" data-tip="Open classic admin">
      {!! $I::svg('sidebar', 15) !!}<span>Open classic admin</span>
    </a>
  </div>
</aside>
