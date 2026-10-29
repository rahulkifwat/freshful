@extends('layout.master')

@section('title', 'Freshful | Fresh food for every day')
@section('meta_description', 'Shop fresh meat, seafood, fruits, vegetables and everyday groceries from Freshful. Find your favourites and order online.')

@push('styles')
<style>
    .freshful-home { --ff-green:#176b45; --ff-green-dark:#0e4b32; --ff-lime:#d8efb0; --ff-ink:#202923; --ff-muted:#647168; --ff-cream:#f7f8f2; --ff-accent:linear-gradient(110deg,#F70202 0%,#FF5500 100%); color:var(--ff-ink); }
    .freshful-home .container-fluid { max-width:1440px; }
    .ff-hero { position:relative; overflow:hidden; margin:1rem auto 0; border:1px solid #edf0e8; border-radius:24px; background:linear-gradient(115deg,#f1f7e9 0%,#f8f7e9 55%,#e7f2db 100%); min-height:330px; box-shadow:0 12px 34px rgba(31,49,37,.06); }
    .ff-hero::after { content:""; position:absolute; width:390px; height:390px; border-radius:50%; background:rgba(216,239,176,.55); right:10%; top:-210px; pointer-events:none; }
    .ff-hero-copy { position:relative; z-index:1; padding:clamp(1.5rem,4vw,3.25rem); }
    .ff-eyebrow { display:inline-flex; align-items:center; gap:.5rem; color:var(--ff-green); font-size:.8rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
    .ff-hero h1 { max-width:650px; margin:1rem 0; font-size:clamp(2.5rem,5vw,4.5rem); line-height:1.04; letter-spacing:-.045em; font-weight:800; }
    .ff-hero p { max-width:530px; color:#56645a; font-size:1.1rem; line-height:1.7; }
    .ff-btn-primary { display:inline-flex; align-items:center; gap:.55rem; border:0; border-radius:999px; padding:.78rem 1.25rem; background:var(--ff-accent); color:#fff !important; font-weight:700; text-decoration:none; box-shadow:0 10px 22px rgba(247,2,2,.16); transition:transform .2s,filter .2s; }
    .ff-btn-primary:hover { filter:saturate(1.12) brightness(.97); transform:translateY(-2px); }
    .ff-btn-quiet { color:var(--ff-green) !important; text-decoration:none; font-weight:700; }
    .ff-hero-visual { position:relative; z-index:1; min-height:290px; display:flex; align-items:center; justify-content:center; padding:1.25rem; }
    .ff-hero-orbit { position:absolute; width:min(29vw,350px); aspect-ratio:1; border:1px solid rgba(23,107,69,.16); border-radius:50%; }
    .ff-hero-product { position:absolute; width:clamp(150px,19vw,240px); aspect-ratio:1; border-radius:28px; overflow:hidden; background:#fff; box-shadow:0 24px 55px rgba(36,67,43,.17); }
    .ff-hero-product img { width:100%; height:100%; object-fit:cover; }
    .ff-hero-product:nth-of-type(2) { transform:translate(-36%,-12%) rotate(-8deg); }
    .ff-hero-product:nth-of-type(3) { transform:translate(31%,-3%) rotate(7deg); }
    .ff-hero-product:nth-of-type(4) { width:clamp(110px,14vw,170px); transform:translate(5%,49%) rotate(-2deg); }
    .ff-hero-badge { position:absolute; z-index:2; right:8%; bottom:12%; padding:.8rem 1rem; border-radius:18px; background:#fff; color:var(--ff-green); font-weight:700; box-shadow:0 12px 30px rgba(36,67,43,.13); }
    .ff-section { padding:clamp(1.65rem,3vw,2.7rem) 0; }
    .ff-section-heading { display:flex; align-items:end; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; }
    .ff-section-heading h2 { margin:0; font-size:clamp(1.55rem,2.6vw,2.25rem); font-weight:800; letter-spacing:-.03em; }
    .ff-section-heading p { margin:.35rem 0 0; color:var(--ff-muted); }
    .ff-category-grid { display:grid; grid-template-columns:repeat(8,minmax(0,1fr)); gap:1rem; }
    .ff-category { min-width:0; padding:1rem .65rem .85rem; border:1px solid #edf0e8; border-radius:20px; color:var(--ff-ink) !important; text-align:center; text-decoration:none; background:#fff; transition:transform .2s,box-shadow .2s,border-color .2s; }
    .ff-category:hover { transform:translateY(-4px); border-color:#c8dfbf; box-shadow:0 12px 28px rgba(36,67,43,.09); }
    .ff-category img { width:clamp(58px,6.5vw,88px); height:clamp(58px,6.5vw,88px); display:block; margin:0 auto .7rem; border-radius:50%; object-fit:cover; background:var(--ff-cream); }
    .ff-category span { display:block; font-size:.9rem; font-weight:700; line-height:1.25; }
    .ff-trust { padding:.65rem 0; background:#f7f8f4; }
    .ff-trust-item { display:flex; align-items:center; gap:.65rem; height:100%; padding:.55rem .7rem; border:1px solid #edf0e8; border-radius:14px; background:#fff; box-shadow:0 4px 14px rgba(31,49,37,.035); }
    .ff-trust-item img { width:36px; height:36px; object-fit:contain; flex:none; }
    .ff-trust-item strong { display:block; font-size:.93rem; }
    .ff-trust-item a { color:var(--ff-green) !important; font-size:.8rem; font-weight:600; }
    .ff-product-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; perspective:1100px; }
    .ff-product-card { display:flex; flex-direction:column; height:100%; overflow:hidden; border:1px solid #edf0e8; border-radius:20px; background:#fff; box-shadow:0 6px 20px rgba(31,49,37,.04); transform:translateZ(0); transform-style:preserve-3d; transition:transform .28s cubic-bezier(.2,.75,.25,1),box-shadow .28s; will-change:transform; }
    .ff-product-card:hover { transform:translateY(-6px) rotateX(2deg) rotateY(-1.5deg); box-shadow:0 20px 38px rgba(31,49,37,.15); }
    .ff-product-photo { position:relative; display:block; aspect-ratio:1.55; overflow:hidden; border-radius:20px 20px 0 0; background:#f5f6f1; }
    .ff-product-photo img { width:100%; height:100%; object-fit:cover; transition:transform .35s; }
    .ff-product-card:hover .ff-product-photo img { transform:scale(1.04); }
    /* Suppress the legacy percentage pill; the product card has one savings chip only. */
    .freshful-home .ff-product-tag { display:none!important; }
    .freshful-home .ff-product-photo .ff-product-savings { position:absolute!important; z-index:3!important; top:.75rem!important; right:auto!important; bottom:auto!important; left:.75rem!important; display:inline-flex!important; width:auto!important; min-width:0!important; max-width:calc(100% - 1.5rem)!important; padding:.4rem .72rem!important; border:1px solid rgba(255,255,255,.75)!important; border-radius:999px!important; background:linear-gradient(110deg,#f70202 0%,#ff5500 100%)!important; color:#fff!important; font-size:.76rem!important; font-weight:800!important; line-height:1.2!important; white-space:nowrap!important; box-shadow:0 4px 12px rgba(173,31,21,.22)!important; }
    .ff-product-quality { position:absolute; bottom:0; left:0; padding:.42rem .8rem; border-radius:0 10px 0 0; background:#d81e32; color:#fff; font-size:.73rem; font-weight:700; }
    .ff-product-info { position:relative; z-index:1; display:flex; flex:1; flex-direction:column; margin-top:0; padding:1rem; border:0; border-radius:0!important; background:#fff; box-shadow:none; }
    .ff-product-category { color:var(--ff-green); font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; }
    .ff-product-name { display:block; min-height:2.8em; margin:.35rem 0 .4rem; color:var(--ff-ink) !important; font-size:1rem; font-weight:750; line-height:1.4; text-decoration:none; }
    .ff-product-unit { color:var(--ff-muted); font-size:.83rem; }
    .ff-product-bottom { display:flex; align-items:center; justify-content:space-between; gap:.6rem; margin-top:auto; padding-top:1rem; }
    .ff-product-actions { display:flex; align-items:center; gap:.55rem; flex:none; }
    .ff-price { color:#151515; font-size:1.2rem; font-weight:800; white-space:nowrap; }
    .ff-price small { display:block; color:#879188; font-size:.76rem; font-weight:500; }
    .ff-old-price { display:block; color:#879188; font-size:.78rem; font-weight:500; text-decoration:line-through; }
    .ff-discount { display:inline-block; margin-top:.15rem; color:#b84b22; font-size:.72rem; font-weight:750; }
    .ff-add { flex:none; border:0; border-radius:999px; padding:.62rem .95rem; background:var(--ff-accent); color:#fff; font-size:.82rem; font-weight:700; }
    .ff-add:hover { filter:brightness(.96); }
    .ff-quantity-control { flex:none; }
    .ff-quantity-stepper { display:flex; align-items:center; gap:.5rem; padding:.22rem; border:1px solid #dce8d7; border-radius:999px; background:#f7fbf4; }
    .ff-quantity-stepper[hidden] { display:none; }
    .ff-quantity-button { display:grid; place-items:center; width:30px; height:30px; border:0; border-radius:50%; background:var(--ff-accent); color:#fff; font-size:1.1rem; font-weight:700; line-height:1; }
    .ff-quantity-value { min-width:1.1rem; color:#151515; font-weight:800; text-align:center; }
    .ff-compare-button { display:grid; place-items:center; width:34px; height:34px; flex:none; border:1px solid #ecefe9; border-radius:50%; background:#fff; color:#59645c; }
    .ff-compare-button:hover { border-color:#ffbca8; color:#e63a17; }
    .ff-offers { background:#faf8f1; }
    .ff-deal-banner { display:block; flex-basis:clamp(255px,31vw,338px); padding:0; border-left:1px solid #edf0e8; }
    .ff-deal-banner a,.ff-deal-banner img { display:block; width:100%; height:100%; min-height:124px; }
    .ff-deal-banner img { object-fit:cover; }
    .ff-deals-empty { padding:1.1rem 1.25rem; border:1px dashed #d9dfd8; border-radius:14px; background:#fff; color:#647168; }
    .ff-deals-heading { display:flex; align-items:end; justify-content:space-between; gap:1rem; margin-bottom:1.1rem; }
    .ff-deals-kicker { display:inline-flex; align-items:center; gap:.4rem; padding:.28rem .65rem; border:1px solid #ffd2c7; border-radius:999px; background:#fff3ef; color:#ed3214; font-size:.72rem; font-weight:800; }
    .ff-deals-heading h2 { margin:.45rem 0 0; color:#202923; font-size:clamp(1.45rem,2.5vw,2rem); font-weight:850; letter-spacing:-.03em; }
    .ff-deals-controls { display:flex; gap:.45rem; }
    .ff-deals-controls button { display:grid; place-items:center; width:38px; height:38px; border:1px solid #e8e9e5; border-radius:50%; background:#fff; color:#27332b; }
    .ff-deals-track { display:flex; gap:1rem; overflow-x:auto; padding:.15rem .15rem .65rem; scroll-snap-type:x mandatory; scrollbar-width:thin; }
    .ff-deal-card { --deal-color:#ff2015; display:flex; align-items:center; gap:.95rem; flex:0 0 clamp(255px,31vw,338px); min-height:124px; padding:1rem; overflow:hidden; border:1px solid #edf0e8; border-left:4px solid var(--deal-color); border-radius:19px; background:#fff; color:var(--ff-ink); box-shadow:0 7px 20px rgba(31,49,37,.065); scroll-snap-align:start; }
    .ff-deal-card:nth-child(4n + 2) { --deal-color:#11a451; }
    .ff-deal-card:nth-child(4n + 3) { --deal-color:#2967f5; }
    .ff-deal-card:nth-child(4n + 4) { --deal-color:#7b35ec; }
    .ff-deal-burst { display:grid; place-items:center; flex:none; width:52px; height:52px; color:#fff; background:var(--deal-color); clip-path:polygon(50% 0%,61% 13%,78% 6%,83% 24%,100% 30%,92% 48%,100% 64%,82% 73%,81% 92%,62% 88%,50% 100%,39% 87%,21% 94%,17% 75%,0% 68%,8% 50%,0% 34%,18% 25%,19% 7%,38% 13%); filter:drop-shadow(0 4px 5px rgba(0,0,0,.15)); font-size:1.3rem; font-weight:900; }
    .ff-deal-copy { min-width:0; flex:1; }
    .ff-deal-copy strong { display:block; color:#18221b; font-size:1rem; line-height:1.2; }
    .ff-deal-copy p { min-height:2.3em; margin:.2rem 0 .35rem; color:#788179; font-size:.77rem; line-height:1.35; }
    .ff-deal-code { display:inline-flex; align-items:center; gap:.4rem; max-width:100%; padding:.22rem .55rem; border:1px dashed var(--deal-color); border-radius:7px; background:#fff9f7; color:var(--deal-color); font-size:.76rem; font-weight:800; letter-spacing:.02em; }
    .ff-deal-code:hover { background:#fff0eb; }
    .ff-offer-card { overflow:hidden; border-radius:18px; background:#fff; box-shadow:0 8px 24px rgba(31,49,37,.07); }
    .ff-offer-card img { display:block; width:100%; aspect-ratio:2 / 1; height:auto; object-fit:cover; }
    .ff-banner-carousel { position:relative; overflow:hidden; margin:1rem auto 0; border:1px solid #ecefe9; border-radius:22px; background:#f5f6f2; box-shadow:0 12px 34px rgba(31,49,37,.07); }
    .ff-banner-track,.ff-offer-track { display:flex; overflow-x:auto; scroll-snap-type:x mandatory; scrollbar-width:none; scroll-behavior:smooth; }
    .ff-banner-track::-webkit-scrollbar,.ff-offer-track::-webkit-scrollbar { display:none; }
    .ff-banner-slide { position:relative; flex:0 0 100%; scroll-snap-align:start; height:clamp(175px,20vw,260px); background:#f5f6f2; }
    .ff-banner-slide img { display:block; width:100%; height:100%; object-fit:cover; }
    .ff-banner-arrow { position:absolute; z-index:2; top:50%; display:grid; place-items:center; width:40px; height:40px; border:0; border-radius:50%; background:rgba(255,255,255,.92); color:#202923; box-shadow:0 4px 14px rgba(0,0,0,.13); transform:translateY(-50%); }
    .ff-banner-arrow.prev { left:14px; } .ff-banner-arrow.next { right:14px; }
    .ff-category-wrap { position:relative; }
    .ff-category-grid { display:flex; gap:.8rem; overflow:auto hidden; scroll-snap-type:x mandatory; scroll-behavior:smooth; scrollbar-width:none; padding:.15rem .1rem .6rem; }
    .ff-category-grid::-webkit-scrollbar { display:none; }
    .ff-category { flex:0 0 clamp(112px,12vw,154px); scroll-snap-align:start; }
    .ff-category-controls { display:flex; align-items:center; gap:.5rem; }
    .ff-scroll-arrow { display:grid; place-items:center; width:36px; height:36px; border:1px solid #e8ebe5; border-radius:50%; background:#fff; color:#252a26; transition:all .2s; }
    .ff-scroll-arrow:hover { border-color:#ffb8a4; color:#e73816; box-shadow:0 4px 12px rgba(247,2,2,.1); }
    .ff-offer-track { gap:1rem; padding:.15rem .1rem .7rem; }
    .ff-offer-slide { flex:0 0 calc(33.333% - .67rem); scroll-snap-align:start; }
    .ff-offer-card img { display:block; width:100%; aspect-ratio:2 / 1; height:auto; object-fit:cover; }
    @media(max-width:767px) { .ff-offer-slide { flex-basis:86%; } .ff-banner-slide { height:clamp(135px,38vw,210px); } .ff-banner-carousel { border-radius:16px; } }
    .ff-promo-grid img { width:100%; aspect-ratio:1.8; object-fit:cover; border:1px solid #edf0e8; border-radius:16px; box-shadow:0 6px 20px rgba(31,49,37,.05); }
    .ff-offer-ribbon { overflow:hidden; margin:.8rem auto 0; padding:.45rem 0; border-radius:999px; color:#fff; background:linear-gradient(110deg,#F70202 0%,#FF5500 100%); }
    .ff-offer-ribbon-track { display:flex; width:max-content; animation:ff-ribbon-scroll 32s linear infinite; }
    .ff-offer-ribbon span { padding:0 2.4rem; font-size:.78rem; font-weight:700; white-space:nowrap; }
    @keyframes ff-ribbon-scroll { to { transform:translateX(-50%); } }
    .ff-app-strip { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border:1px solid #edf0e8; border-radius:18px; background:#fff; box-shadow:0 6px 20px rgba(31,49,37,.05); }
    .ff-app-strip h2 { margin:0; font-size:1.15rem; font-weight:800; }
    .ff-app-strip img { width:min(100%,340px); max-height:56px; object-fit:contain; }
    .ff-newsletter { border-radius:22px; background:var(--ff-green); }
    .ff-newsletter h2 { color:#fff; font-size:1.5rem; font-weight:800; }
    .ff-newsletter p { margin:0; color:rgba(255,255,255,.82); }
    .ff-newsletter .form-control { border:0; border-radius:999px 0 0 999px; }
    .ff-newsletter .btn { border:0; border-radius:0 999px 999px 0; background:#d8efb0; color:var(--ff-green-dark); }
    @media (prefers-reduced-motion: reduce) { .ff-product-card { transition:none; } .ff-product-card:hover { transform:translateY(-2px); } }
    @media (max-width: 1199px) { .ff-product-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media (max-width: 767px) { .ff-product-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width: 575px) { .ff-hero { margin-top:.75rem; border-radius:18px; min-height:0; } .ff-hero-copy { padding:1.5rem 1.1rem .2rem; } .ff-hero h1 { font-size:2.1rem; } .ff-hero p { font-size:.95rem; } .ff-hero-visual { min-height:215px; } .ff-hero-orbit { width:210px; } .ff-hero-product { width:135px; border-radius:18px; } .ff-hero-product:nth-of-type(4) { width:100px; } .ff-hero-badge { right:3%; bottom:7%; font-size:.72rem; } .ff-section { padding:1.6rem 0; } .ff-category { padding:.65rem .2rem; border-radius:13px; } .ff-category img { width:54px; height:54px; } .ff-category span { font-size:.69rem; } .ff-product-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.6rem; } .ff-product-info { padding:.75rem; } .ff-product-name { font-size:.84rem; } .ff-product-bottom { align-items:center; flex-wrap:wrap; } .ff-product-actions { gap:.3rem; } .ff-compare-button { width:30px; height:30px; } .ff-price { font-size:1rem; } .ff-add { padding:.48rem .7rem; } .ff-trust-item { padding:.45rem; gap:.4rem; } .ff-trust-item img { width:30px; height:30px; } .ff-app-strip { align-items:flex-start; flex-direction:column; } }
</style>
@endpush

@section('content')
<main class="freshful-home">
    <div class="container-fluid px-3 px-lg-4">
        @if ($banners->isNotEmpty())
            <section class="ff-banner-carousel" aria-label="Freshful promotions">
                <div class="ff-banner-track" id="ff-banner-track">
                    @foreach ($banners as $banner)
                        <a class="ff-banner-slide" href="{{ filled($banner->url) ? $banner->url : url('/view_product/all') }}" aria-label="{{ $banner->title ?: 'Shop Freshful' }}">
                            <img src="{{ asset('uploads/images/banners/'.$banner->image) }}" alt="{{ $banner->title ?: 'Freshful promotion' }}" @if (!$loop->first) loading="lazy" @endif>
                        </a>
                    @endforeach
                </div>
                @if ($banners->count() > 1)
                    <button class="ff-banner-arrow prev" type="button" data-scroll-target="ff-banner-track" data-scroll-direction="-1" aria-label="Previous banner"><i class="bi bi-chevron-left"></i></button>
                    <button class="ff-banner-arrow next" type="button" data-scroll-target="ff-banner-track" data-scroll-direction="1" aria-label="Next banner"><i class="bi bi-chevron-right"></i></button>
                @endif
            </section>
        @else
        <section class="ff-hero row g-0 align-items-center" aria-labelledby="freshful-hero-title">
            <div class="col-lg-7">
                <div class="ff-hero-copy">
                    <span class="ff-eyebrow"><i class="bi bi-stars" aria-hidden="true"></i> A little fresher, every day</span>
                    <h1 id="freshful-hero-title">Good food starts with Freshful.</h1>
                    <p>Shop fresh meat, seafood, fruits, vegetables and everyday favourites—all in one place for your kitchen.</p>
                    <div class="d-flex flex-wrap align-items-center gap-3 mt-4">
                        <a class="ff-btn-primary" href="{{ url('/view_product/all') }}">Explore the store <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        <a class="ff-btn-quiet" href="{{ url('/why-freshful') }}">Why Freshful?</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="ff-hero-visual" aria-label="Freshful customer favourites">
                    <div class="ff-hero-orbit" aria-hidden="true"></div>
                    @forelse ($bestSellers->take(3) as $heroProduct)
                        <a class="ff-hero-product" href="{{ route('product_detail', ['id' => $heroProduct->id]) }}" aria-label="Shop {{ $heroProduct->product_name }}">
                            <img src="{{ asset('uploads/images/products/'.$heroProduct->product_image) }}" alt="{{ $heroProduct->product_name }}" loading="lazy">
                        </a>
                    @empty
                        @foreach ($categories->take(3) as $heroCategory)
                            <a class="ff-hero-product" href="{{ url('/view_product/'.$heroCategory->id) }}" aria-label="Shop {{ $heroCategory->name }}">
                                <img src="{{ asset('uploads/images/'.$heroCategory->circle_image) }}" alt="{{ $heroCategory->name }}" loading="lazy">
                            </a>
                        @endforeach
                    @endforelse
                    <div class="ff-hero-badge"><i class="bi bi-heart-fill me-1" aria-hidden="true"></i> Freshful favourites</div>
                </div>
            </div>
        </section>
        @endif

        <div class="ff-offer-ribbon" aria-label="Freshful offers">
            <div class="ff-offer-ribbon-track" aria-hidden="true">
                @foreach (range(1, 2) as $repeat)
                    <span><i class="bi bi-stars me-2"></i>Fresh picks, bright prices</span>
                    <span><i class="bi bi-truck me-2"></i>Freshness delivered to your door</span>
                    <span><i class="bi bi-basket2 me-2"></i>Explore today's favourites</span>
                @endforeach
            </div>
        </div>

        <section class="ff-section" aria-labelledby="category-title">
            <div class="ff-section-heading">
                <div><h2 id="category-title">Shop by category</h2><p>Find what you need for today's meal.</p></div>
                <div class="ff-category-controls">
                    <button class="ff-scroll-arrow ff-scroll-prev" type="button" data-scroll-target="home-category-track" data-scroll-direction="-1" aria-label="Previous categories"><i class="bi bi-chevron-left"></i></button>
                    <button class="ff-scroll-arrow ff-scroll-next" type="button" data-scroll-target="home-category-track" data-scroll-direction="1" aria-label="Next categories"><i class="bi bi-chevron-right"></i></button>
                    <a class="ff-btn-quiet" href="{{ route('categories') }}">View all <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                </div>
            </div>
            <div class="ff-category-grid" id="home-category-track">
                @forelse ($categories->unique('name') as $category)
                    <a href="{{ url('/view_product/'.$category->id) }}" class="ff-category">
                        <img src="{{ asset('uploads/images/'.$category->circle_image) }}" alt="" loading="lazy">
                        <span>{{ $category->name }}</span>
                    </a>
                @empty
                    <p class="text-muted">Categories are being updated. Browse all products in the meantime.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="ff-trust" aria-label="Freshful quality commitments">
        <div class="container-fluid px-3 px-lg-4">
            <div class="row row-cols-2 row-cols-lg-5 g-2 g-lg-3">
                <div class="col"><div class="ff-trust-item"><img src="{{ asset('assets/image/why/FreshlyCut.png') }}" alt="" loading="lazy"><div><strong>Freshly cut</strong><a href="{{ url('/why-freshful') }}">How we work</a></div></div></div>
                <div class="col"><div class="ff-trust-item"><img src="{{ asset('assets/image/why/FarmFreshEveryday.png') }}" alt="" loading="lazy"><div><strong>Farm fresh every day</strong><a href="{{ url('/why-freshful') }}">How we work</a></div></div></div>
                <div class="col"><div class="ff-trust-item"><img src="{{ asset('assets/image/why/SafeHealthy.png') }}" alt="" loading="lazy"><div><strong>Safe &amp; healthy</strong><a href="{{ url('/why-freshful') }}">Our standards</a></div></div></div>
                <div class="col"><div class="ff-trust-item"><img src="{{ asset('assets/image/why/AntibioticFree.png') }}" alt="" loading="lazy"><div><strong>Thoughtful sourcing</strong><a href="{{ url('/why-freshful') }}">Our standards</a></div></div></div>
                <div class="col"><div class="ff-trust-item"><img src="{{ asset('assets/image/why/LocalyProduced.png') }}" alt="" loading="lazy"><div><strong>Supporting local</strong><a href="{{ url('/why-freshful') }}">Our story</a></div></div></div>
            </div>
        </div>
    </section>

    <div class="container-fluid px-3 px-lg-4">
        {{-- When supplied by the home controller, render every active admin product tag as its own section.
             Expected shape: [['name' => 'Fresh Dairy', 'products' => Collection], ...]. --}}
        @if (isset($homeTagSections) && count($homeTagSections))
            @foreach ($homeTagSections as $sectionIndex => $homeTagSection)
                @if (!empty($homeTagSection['products']) && count($homeTagSection['products']))
                    <section class="ff-section" aria-labelledby="home-tag-{{ $sectionIndex }}">
                        <div class="ff-section-heading">
                            <div><h2 id="home-tag-{{ $sectionIndex }}">{{ $homeTagSection['name'] }}</h2></div>
                            <a class="ff-btn-quiet" href="{{ url('/view_product/all/'.rawurlencode($homeTagSection['name'])) }}">View all <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                        </div>
                        <div class="ff-product-grid">
                            @foreach ($homeTagSection['products'] as $product)
                                @include('partials.home-product-card', ['product' => $product])
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        @elseif ($bestSellers->isNotEmpty())
            <section class="ff-section" aria-labelledby="best-sellers-title">
                <div class="ff-section-heading"><div><h2 id="best-sellers-title">Customer favourites</h2><p>Popular picks from across the store.</p></div><a class="ff-btn-quiet" href="{{ url('/view_product/all') }}">Shop all <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a></div>
                <div class="ff-product-grid">
                    @foreach ($bestSellers as $product)
                        @include('partials.home-product-card', ['product' => $product])
                    @endforeach
                </div>
            </section>
        @endif

        <section class="ff-section ff-offers" aria-label="Current offers">
            <div class="container-fluid px-3 px-lg-4">
                <div class="ff-deals-heading">
                    <div><span class="ff-deals-kicker"><i class="bi bi-tag-fill" aria-hidden="true"></i> Limited time</span><h2>Deals for you</h2></div>
                    @if ($promotions->count() > 2 || ($promotions->isEmpty() && $coupons->count() > 2))
                        <div class="ff-deals-controls" aria-label="Scroll offers">
                            <button type="button" onclick="document.getElementById('ff-deals-track').scrollBy({left:-360,behavior:'smooth'})" aria-label="Previous offers"><i class="bi bi-chevron-left"></i></button>
                            <button type="button" onclick="document.getElementById('ff-deals-track').scrollBy({left:360,behavior:'smooth'})" aria-label="More offers"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    @endif
                </div>
                <div class="ff-deals-track" id="ff-deals-track">
                    @forelse ($promotions as $promotion)
                        <article class="ff-deal-card">
                            <span class="ff-deal-burst" aria-hidden="true">%</span>
                            <div class="ff-deal-copy">
                                <strong>{{ rtrim(rtrim(number_format((float) $promotion->percentage, 2), '0'), '.') }}% Off</strong>
                                <p>{{ $promotion->description ?: ('Min. order ₹'.number_format((float) ($promotion->min_amount ?? 0), 0).(!empty($promotion->user_eligible) && $promotion->user_eligible !== 'everyone' ? ' · '.ucwords($promotion->user_eligible).' only' : '')) }}</p>
                                <button class="ff-deal-code" type="button" onclick="copyFreshfulPromo(@js($promotion->title), this)">{{ $promotion->title }} <i class="bi bi-copy" aria-hidden="true"></i></button>
                            </div>
                        </article>
                    @empty
                        @forelse ($coupons->take(6) as $coupon)
                            <article class="ff-deal-card ff-deal-banner">
                                <a href="{{ url('/view_product/all') }}" aria-label="Browse Freshful offers">
                                    <img src="{{ asset('assets/image/discount/'.$coupon->image) }}" alt="{{ $coupon->title ?? 'Freshful offer' }}" loading="lazy">
                                </a>
                            </article>
                        @empty
                            <div class="ff-deals-empty">New offers are being prepared. Check back soon.</div>
                        @endforelse
                    @endforelse
                </div>
            </div>
        </section>

        @if ((!isset($homeTagSections) || !count($homeTagSections)) && $meatAndSeafoods->isNotEmpty())
            <section class="ff-section" aria-labelledby="meat-seafood-title">
                <div class="ff-section-heading"><div><h2 id="meat-seafood-title">Meat &amp; seafood</h2><p>Browse popular cuts and seafood favourites.</p></div><a class="ff-btn-quiet" href="{{ url('/view_product/all/Meat & Seafoods') }}">View all <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a></div>
                <div class="ff-product-grid">
                    @foreach ($meatAndSeafoods as $product)
                        @include('partials.home-product-card', ['product' => $product])
                    @endforeach
                </div>
            </section>
        @endif

        <section class="ff-section ff-promo-grid" aria-label="More from Freshful">
            <div class="row g-3">
                <div class="col-md-4"><img src="{{ asset('assets/image/other/2.png') }}" alt="Discover more at Freshful" class="img-fluid" loading="lazy"></div>
                <div class="col-md-4"><img src="{{ asset('assets/image/other/3.png') }}" alt="Freshful picks for your kitchen" class="img-fluid" loading="lazy"></div>
                <div class="col-md-4"><img src="{{ asset('assets/image/other/Fl5od1667877499.png') }}" alt="Explore Freshful" class="img-fluid" loading="lazy"></div>
            </div>
        </section>

        <section class="mb-4" aria-label="Freshful app download">
            <div class="ff-app-strip">
                <div><h2>Get the Freshful app</h2><p class="text-muted small mb-0">Shop your favourites wherever you are.</p></div>
                <img src="{{ asset('assets/image/other/app_stroe.png') }}" alt="Available on the App Store and Google Play" loading="lazy">
            </div>
        </section>

        <section class="ff-newsletter p-4 p-md-5 mb-5" aria-labelledby="newsletter-title">
            <div class="row align-items-center g-3">
                <div class="col-md-8"><h2 id="newsletter-title">A question about your order?</h2><p>Our team is here to help with products, delivery areas and anything else you need.</p></div>
                <div class="col-md-4 text-md-end"><a class="ff-btn-primary" href="{{ url('/contact_us') }}">Contact Freshful <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
            </div>
        </section>
    </div>
</main>
@endsection
@push('scripts')
<script>
function copyFreshfulPromo(code, button) {
    const original = button.innerHTML;
    const copied = () => { button.innerHTML = 'Copied <i class="bi bi-check2" aria-hidden="true"></i>'; setTimeout(() => button.innerHTML = original, 1300); };
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(code).then(copied);
    else { const field = document.createElement('textarea'); field.value = code; document.body.appendChild(field); field.select(); document.execCommand('copy'); field.remove(); copied(); }
}
</script>
@endpush
