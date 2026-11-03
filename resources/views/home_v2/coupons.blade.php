@php
    $sfColors = (array) config('storefront.coupon_colors', ['#0b6f78']);
@endphp
@if ($promotions->isNotEmpty())
    <section class="ffh-section" aria-labelledby="ffh-coupons-title">
        <div class="ffh-head">
            <div><h2 id="ffh-coupons-title">Coupons for you</h2><p>Tap a code to copy it, then apply it at checkout</p></div>
        </div>
        <div class="ffh-coupons">
            @foreach ($promotions->take(7) as $promotion)
                @php
                    $cColor = $sfColors[$loop->index % count($sfColors)];
                    $cPct = rtrim(rtrim(number_format((float) $promotion->percentage, 2), '0'), '.');
                    $cMin = (int) ($promotion->min_amount ?? 0);
                    $cMax = (int) ($promotion->max_amount ?? 0);
                    $cKicker = ($promotion->user_eligible ?? '') === 'new user' ? 'First order' : 'For everyone';
                    $cDesc = $cMin > 0 ? 'On orders above ₹'.number_format($cMin) : 'On any order';
                    if ($cMax > 0) { $cDesc .= ' · up to ₹'.number_format($cMax); }
                    $cExp = '';
                    if (! empty($promotion->expiry) && $promotion->expiry !== '0') {
                        try { $cExp = 'Till '.\Illuminate\Support\Carbon::parse($promotion->expiry)->format('j M'); } catch (\Throwable $e) { $cExp = ''; }
                    }
                @endphp
                <div class="ffh-coupon" style="background:{{ $cColor }}" title="{{ $promotion->description }}">
                    <div class="ffh-coupon-main">
                        <span class="ffh-coupon-kicker">{{ $cKicker }}</span>
                        <strong class="ffh-coupon-big">{{ $cPct }}% OFF</strong>
                        <span class="ffh-coupon-desc">{{ $cDesc }}</span>
                    </div>
                    <div class="ffh-coupon-side">
                        <button type="button" class="ffh-coupon-code" style="color:{{ $cColor }}" data-ffh-copy="{{ $promotion->title }}" aria-label="Copy code {{ $promotion->title }}">{{ $promotion->title }} <i class="bi bi-copy" aria-hidden="true"></i></button>
                        @if ($cExp !== '')
                            <span class="ffh-coupon-exp">{{ $cExp }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
            <a class="ffh-coupon ffh-coupon-refer" href="{{ url('/refer_earn') }}">
                <div class="ffh-coupon-main">
                    <span class="ffh-coupon-kicker">Refer &amp; earn</span>
                    <strong class="ffh-coupon-big" style="font-size:20px">Refer a friend</strong>
                    <span class="ffh-coupon-desc">Earn wallet money when their first order is ₹400+</span>
                </div>
                <div class="ffh-coupon-side"><span class="ffh-coupon-code" style="color:#2d2a6e">Invite <i class="bi bi-arrow-right" aria-hidden="true"></i></span></div>
            </a>
        </div>
    </section>
@elseif ($coupons->isNotEmpty())
    <section class="ffh-section" aria-labelledby="ffh-coupons-title">
        <div class="ffh-head"><div><h2 id="ffh-coupons-title">Deals for you</h2></div></div>
        <div class="ffh-coupons">
            @foreach ($coupons as $coupon)
                <a class="ffh-coupon-img" href="{{ url('/view_product/all') }}" aria-label="Browse Freshful offers">
                    <img src="{{ asset('assets/image/discount/'.$coupon->image) }}" alt="{{ $coupon->title ?? 'Freshful offer' }}" loading="lazy">
                </a>
            @endforeach
        </div>
    </section>
@endif
