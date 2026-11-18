{{-- Home update 9 (8 Nov 2026): round category pictures, logo placeholder, coupon row, banner animation and colours. Safe to delete this file and its @include. --}}
@php
    $ffs = app(\App\Services\Storefront\StorefrontSettings::class);
    $ffd = \App\Services\Storefront\StorefrontSettings::DEFAULTS;
    $ffc = function ($k) use ($ffs, $ffd) {
        $v = (string) $ffs->get($k, $ffd[$k] ?? '');
        return \App\Services\Storefront\StorefrontSettings::validHex($v) ? strtoupper($v) : strtoupper((string) ($ffd[$k] ?? '#000000'));
    };
    $ffChanged = fn ($k) => $ffc($k) !== strtoupper((string) ($ffd[$k] ?? ''));
    $ffAnim = in_array($ffs->get('hero_anim', 'slide'), ['slide', 'fade', 'zoom'], true) ? $ffs->get('hero_anim', 'slide') : 'slide';
    $ffLogo = asset('assets/image/logo.png');
@endphp
<style>
/* ffh-v9 round category pictures: the circle is the frame, the picture is trimmed to fit, never stretched */
.ffh .ffh-cat .ffh-cat-pic{display:block;position:relative;width:min(150px,100%);aspect-ratio:1/1;border-radius:50%;overflow:hidden;background:#f4f2ef;box-shadow:0 12px 22px -8px rgba(40,25,15,.38);transition:transform .35s cubic-bezier(.2,.8,.2,1),box-shadow .35s}
.ffh .ffh-cat .ffh-cat-pic img{position:absolute;inset:0;display:block;width:100%;height:100%;aspect-ratio:auto;border-radius:0;object-fit:cover;filter:none;transform:none}
.ffh .ffh-cat:hover .ffh-cat-pic{transform:translateY(-6px) scale(1.04);box-shadow:0 20px 26px -10px rgba(40,25,15,.42)}
.ffh .ffh-cat:hover .ffh-cat-pic img{transform:none;filter:none}
/* ffh-v9 picture missing: soft blurred Freshful logo instead of an empty box */
.ffh .ffh-noimg{position:relative;background-color:#f4f2ef}
.ffh .ffh-noimg>img{visibility:hidden}
.ffh .ffh-noimg::before{content:"";position:absolute;inset:0;background:url("{{ $ffLogo }}") center/48% auto no-repeat;filter:blur(2px);opacity:.4;pointer-events:none}
/* ffh-v9 coupons: one row, scrolls by itself */
.ffh .ffh-coupons{display:flex!important;grid-template-columns:none!important;overflow-x:auto;scroll-snap-type:x proximity;gap:16px;scrollbar-width:none;padding:6px 2px 16px;margin:-6px -2px -16px}
.ffh .ffh-coupons::-webkit-scrollbar{display:none}
.ffh .ffh-coupons>*{flex:0 0 calc((100% - 52px)/4);min-width:250px;scroll-snap-align:start}
@media (max-width:1200px){.ffh .ffh-coupons>*{flex-basis:calc((100% - 20px)/2)}}
@media (max-width:575px){.ffh .ffh-coupons>*{flex-basis:84%;min-width:0}}
/* ffh-v9 banner animation */
.ffh .ffh-hero-track.ffh-anim-fade,.ffh .ffh-hero-track.ffh-anim-zoom{display:grid;overflow:hidden;scroll-snap-type:none}
.ffh .ffh-anim-fade>.ffh-hero-slide,.ffh .ffh-anim-zoom>.ffh-hero-slide{grid-area:1/1;flex:none;width:100%;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .9s ease,visibility 0s linear .9s}
.ffh .ffh-anim-fade>.ffh-hero-slide.is-on,.ffh .ffh-anim-zoom>.ffh-hero-slide.is-on{opacity:1;visibility:visible;pointer-events:auto;transition:opacity .9s ease}
.ffh .ffh-anim-zoom .ffh-hero-photo{overflow:hidden}
.ffh .ffh-anim-zoom>.ffh-hero-slide.is-on img{animation:ffhzoom 9s ease-out both}
@keyframes ffhzoom{from{transform:scale(1)}to{transform:scale(1.09)}}
@media (prefers-reduced-motion:reduce){.ffh .ffh-anim-zoom>.ffh-hero-slide.is-on img{animation:none}.ffh .ffh-anim-fade>.ffh-hero-slide,.ffh .ffh-anim-zoom>.ffh-hero-slide{transition:none}}
/* ffh-v9 ribbon and picture tiles (same blocks as the app) */
.ffh .ffh-ribbon{display:grid;gap:14px;margin-top:30px}
.ffh .ffh-ribbon a,.ffh .ffh-ribbon .rb{display:block;border-radius:22px;overflow:hidden;line-height:0;background:#f4f2ef}
.ffh .ffh-ribbon img,.ffh .ffh-tiles img{display:block;width:100%;height:auto}
.ffh .ffh-tiles{margin-top:30px}
.ffh .ffh-tiles .tg{display:grid;grid-template-columns:repeat(var(--n,2),minmax(0,1fr));gap:16px}
.ffh .ffh-tiles .tg a,.ffh .ffh-tiles .tg .rb{display:block;border-radius:20px;overflow:hidden;line-height:0;background:#f4f2ef}
/* ffh-size-v1: sensible sizes. Ribbon is a slim centred strip, tiles are compact cards. */
.ffh .ffh-ribbon{justify-items:center}
.ffh .ffh-ribbon a,.ffh .ffh-ribbon .rb{width:100%;max-width:820px;border-radius:18px}
.ffh .ffh-tiles .tg{grid-template-columns:repeat(var(--n,2),minmax(0,400px));justify-content:center;gap:16px}
.ffh .ffh-tiles .tg a,.ffh .ffh-tiles .tg .rb{border-radius:16px}
@media (max-width:991px){.ffh .ffh-ribbon a,.ffh .ffh-ribbon .rb{max-width:100%}.ffh .ffh-tiles .tg{grid-template-columns:repeat(var(--n,2),minmax(0,1fr))}}
@media (max-width:575px){.ffh .ffh-tiles .tg{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.ffh .ffh-ribbon a,.ffh .ffh-ribbon .rb{border-radius:14px}}
/* ffh-size-v2: ribbons two in a row, slim strip (5:1), no stretch */
.ffh .ffh-ribbon{grid-template-columns:repeat(2,minmax(0,1fr));justify-items:stretch;gap:16px}
.ffh .ffh-ribbon a,.ffh .ffh-ribbon .rb{max-width:none;width:100%;aspect-ratio:5/1;border-radius:16px}
.ffh .ffh-ribbon img{width:100%;height:100%;object-fit:cover;object-position:center}
.ffh .ffh-ribbon>:only-child{grid-column:1/-1;width:50%;justify-self:center}
@media (max-width:767px){.ffh .ffh-ribbon{grid-template-columns:1fr;gap:10px}.ffh .ffh-ribbon a,.ffh .ffh-ribbon .rb{aspect-ratio:3/1;border-radius:14px}.ffh .ffh-ribbon>:only-child{width:100%}}
/* ffh-v9 colours chosen in admin (only written when different from the standard look) */
@if ($ffChanged('hero_bg')).ffh .ffh-hero-main,.ffh .ffh-hero-slide:not(.ffh-hero-banner){background:{{ $ffc('hero_bg') }}}@endif
@if ($ffChanged('hero_text')).ffh .ffh-hero-copy h1,.ffh .ffh-hero-price strong{color:{{ $ffc('hero_text') }}}@endif
@if ($ffChanged('hero_btn_from') || $ffChanged('hero_btn_to')).ffh .ffh-btn{background:linear-gradient(110deg,{{ $ffc('hero_btn_from') }},{{ $ffc('hero_btn_to') }})}@endif
@if ($ffChanged('express_bg')).ffh .ffh-tile-white{background:{{ $ffc('express_bg') }}}@endif
@if ($ffChanged('express_text')).ffh .ffh-tile-white h3,.ffh .ffh-tile-white p{color:{{ $ffc('express_text') }}}@endif
@if ($ffChanged('express_icon')).ffh .ffh-tile-white .ffh-tile-icon{background:{{ $ffc('express_icon') }}}@endif
@if ($ffChanged('offer_from') || $ffChanged('offer_to')).ffh .ffh-tile-hot{background:linear-gradient(135deg,{{ $ffc('offer_from') }},{{ $ffc('offer_to') }})}@endif
@if ($ffChanged('offer_text')).ffh .ffh-tile-hot,.ffh .ffh-tile-hot h3,.ffh .ffh-tile-hot p,.ffh .ffh-tile-hot .k{color:{{ $ffc('offer_text') }}!important}@endif
</style>
@push('scripts')
<script>
(function () {
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var CFG = { anim: '{{ $ffAnim }}', auto: {{ $ffs->flag('hero_auto') ? 1 : 0 }}, seconds: {{ $ffs->int('hero_seconds', 6, 2, 30) }}, cScroll: {{ $ffs->flag('coupon_scroll') ? 1 : 0 }}, cSeconds: {{ $ffs->int('coupon_seconds', 3, 2, 15) }} };

    // Missing picture: show the soft Freshful logo (CSS class ffh-noimg).
    var SEL = '.ffv-photo, .ffh-cat-pic, .ffh-hero-photo';
    function mark(img) { var p = img.closest ? img.closest(SEL) : null; if (p) p.classList.add('ffh-noimg'); }
    function check(img) {
        var src = img.getAttribute('src') || '';
        if ((img.complete && img.naturalWidth === 0) || /noimage\.(jpg|jpeg|png)/i.test(src)) mark(img);
    }
    function scan() { document.querySelectorAll('.ffv-photo img, .ffh-cat-pic img, .ffh-hero-photo img').forEach(check); }
    document.addEventListener('error', function (e) { if (e.target && e.target.tagName === 'IMG') mark(e.target); }, true);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan); else scan();
    window.addEventListener('load', scan);

    // Main banner animation. "slide" keeps the existing slider; fade and zoom are handled here.
    var track = document.getElementById('ffh-hero-track');
    if (track) {
        var slides = track.children, n = slides.length;
        if (!CFG.auto || CFG.anim !== 'slide') track.setAttribute('data-ffh-seconds', '0');
        if (n > 1 && CFG.anim !== 'slide') {
            track.classList.add('ffh-anim-' + CFG.anim);
            var dots = document.querySelectorAll('[data-ffh-hero-dot]'), cur = 0, timer = null;
            var show = function (k) {
                cur = (k + n) % n;
                for (var j = 0; j < n; j++) slides[j].classList.toggle('is-on', j === cur);
                dots.forEach(function (d) { d.classList.toggle('on', Number(d.getAttribute('data-ffh-hero-dot')) === cur); });
            };
            var stop = function () { clearInterval(timer); };
            var restart = function () { stop(); if (CFG.auto && !reduce) timer = setInterval(function () { show(cur + 1); }, CFG.seconds * 1000); };
            dots.forEach(function (d) { d.addEventListener('click', function () { show(Number(d.getAttribute('data-ffh-hero-dot'))); restart(); }); });
            track.parentNode.addEventListener('mouseenter', stop);
            track.parentNode.addEventListener('mouseleave', restart);
            show(0); restart();
        }
    }

    // Coupons: one row that moves along by itself, pauses while the visitor touches it.
    document.querySelectorAll('.ffh-coupons').forEach(function (row) {
        if (!CFG.cScroll || reduce) return;
        var paused = false, resume = null;
        setInterval(function () {
            if (paused || row.children.length < 2 || row.scrollWidth <= row.clientWidth + 4) return;
            var w = row.children[0].getBoundingClientRect().width + 16;
            if (row.scrollLeft + row.clientWidth >= row.scrollWidth - 4) row.scrollTo({ left: 0, behavior: 'smooth' });
            else row.scrollBy({ left: w, behavior: 'smooth' });
        }, CFG.cSeconds * 1000);
        ['mouseenter', 'focusin', 'touchstart'].forEach(function (ev) { row.addEventListener(ev, function () { paused = true; clearTimeout(resume); }, { passive: true }); });
        ['mouseleave', 'focusout'].forEach(function (ev) { row.addEventListener(ev, function () { paused = false; }); });
        row.addEventListener('touchend', function () { clearTimeout(resume); resume = setTimeout(function () { paused = false; }, 5000); }, { passive: true });
    });
})();
</script>
@endpush
