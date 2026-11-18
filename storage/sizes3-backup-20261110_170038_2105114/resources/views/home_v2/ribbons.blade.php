{{-- Home update 9: ribbon and picture-tile blocks from Admin > Marketing > App control. The app and the website read the same entries. --}}
@php
    $ffsR = app(\App\Services\Storefront\StorefrontSettings::class);
    $ffOn = $ffsR->flag('show_ribbons') && class_exists(\App\Services\BuyerApp\AppHomeConfig::class);
@endphp
@if ($ffOn)
    @php
        $ffNorm = fn ($x) => rtrim(preg_replace('/[^a-z0-9]/', '', strtolower((string) $x)), 's');
        $ffBlocks = collect(\App\Services\BuyerApp\AppHomeConfig::blocks() ?? [])
            ->filter(fn ($b) => in_array($b['type'] ?? '', ['ribbon', 'tiles'], true) && ! empty($b['items']))->values();
        $ffNames = collect($homeTagSections ?? [])->map(fn ($s) => $ffNorm($s['name']))->all();
        $ffMid = max(0, intdiv(count($ffNames), 2) - 1);
        $ffHere = $ffBlocks->filter(function ($b) use ($ffNorm, $ffNames, $ffAfter, $ffIndex, $ffMid) {
            $after = $ffNorm($b['after'] ?? '');
            if ($after !== '' && in_array($after, $ffNames, true)) {
                return $after === $ffNorm($ffAfter);
            }
            return (int) $ffIndex === $ffMid;
        });
        $ffLink = function ($l) {
            $l = trim((string) $l);
            if ($l === '') { return ''; }
            if (preg_match('#^https?://#i', $l)) { return $l; }
            if ($l[0] === '/') { return url($l); }
            if (preg_match('#^category/(\d+)#', $l, $m)) { return url('/view_product/'.$m[1]); }
            if (preg_match('#^section/(.+)$#', $l, $m)) { return url('/view_product/all/'.rawurlencode($m[1])); }
            if ($l === 'categories') { return route('categories'); }
            return '';
        };
    @endphp
    @foreach ($ffHere as $ffb)
        @if (($ffb['type'] ?? '') === 'ribbon')
            <section class="ffh-section ffh-ribbon" aria-label="Offer">
                @foreach ($ffb['items'] as $ffi)
                    @continue(empty($ffi['image']))
                    @php $ffHref = $ffLink($ffi['link'] ?? ''); @endphp
                    @if ($ffHref !== '')
                        <a href="{{ $ffHref }}"><img src="{{ $ffi['image'] }}" alt="{{ ($ffi['title'] ?? '') !== '' ? $ffi['title'] : 'Freshful offer' }}" loading="lazy" onerror="this.parentNode.style.display='none'"></a>
                    @else
                        <span class="rb"><img src="{{ $ffi['image'] }}" alt="{{ ($ffi['title'] ?? '') !== '' ? $ffi['title'] : 'Freshful offer' }}" loading="lazy" onerror="this.parentNode.style.display='none'"></span>
                    @endif
                @endforeach
            </section>
        @else
            <section class="ffh-section ffh-tiles" aria-label="{{ $ffb['title'] ?? 'Offers' }}">
                @if (! empty($ffb['title']))
                    <div class="ffh-head"><div><h2>{{ $ffb['title'] }}</h2></div></div>
                @endif
                <div class="tg" style="--n:{{ max(1, min(4, count($ffb['items']))) }}">
                    @foreach ($ffb['items'] as $ffi)
                        @continue(empty($ffi['image']))
                        @php $ffHref = $ffLink($ffi['link'] ?? ''); @endphp
                        @if ($ffHref !== '')
                            <a href="{{ $ffHref }}"><img src="{{ $ffi['image'] }}" alt="{{ ($ffi['title'] ?? '') !== '' ? $ffi['title'] : 'Freshful offer' }}" loading="lazy" onerror="this.parentNode.style.display='none'"></a>
                        @else
                            <span class="rb"><img src="{{ $ffi['image'] }}" alt="{{ ($ffi['title'] ?? '') !== '' ? $ffi['title'] : 'Freshful offer' }}" loading="lazy" onerror="this.parentNode.style.display='none'"></span>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
@endif
