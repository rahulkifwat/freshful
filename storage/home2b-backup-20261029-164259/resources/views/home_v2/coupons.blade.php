@php
    $sfColors = (array) config('storefront.coupon_colors', ['#0b6f78']);
@endphp
@if ($promotions->isNotEmpty())
    <section class="ffh-section" aria-labelledby="ffh-coupons-title">
        <div class="ffh-head">
            <div><h2 id="ffh-coupons-title">Coupons for you</h2><p>Tap a code to copy it, then apply it at checkout</p></div>
        </div>
        <div class="ffh-coupons">
            @foreach ($promotions as $promotion)
                @php
                    $cColor = $sfColors[$loop->index % count($sfColors)];
                    $cPct = rtrim(rtrim(number_format((float) $promotion->percentage, 2), '0'), '.');
                    $cMin = (int) ($promotion->min_amount ?? 0);
                    $cMax = (int) ($promotion->max_amount ?? 0);
                    $cKicker = ($promotion->user_eligible ?? '') === 'new user' ? 'First order' : 'For everyone';
                    $cDesc = $promotion->description ?: ($cMin > 0 ? 'on orders above ₹'.number_format($cMin) : 'on any order');
                    $cExp = '';
                    if (! empty($promotion->expiry) && $promotion->expiry !== '0') {
                        try { $cExp = 'Valid till '.\Illuminate\Support\Carbon::parse($promotion->expiry)->format('j M Y'); } catch (\Throwable $e) { $cExp = ''; }
                    }
                    if ($cExp === '' && $cMax > 0) { $cExp = 'Save up to ₹'.number_format($cMax); }
                @endphp
                <div class="ffh-ticket">
                    <div class="ffh-ticket-body" style="background:{{ $cColor }}">
                        <div class="ffh-ticket-kicker">{{ $cKicker }}</div>
                        <div class="ffh-ticket-flat">FLAT</div>
                        <div class="ffh-ticket-big">{{ $cPct }}% OFF</div>
                        <div class="ffh-ticket-desc">{{ \Illuminate\Support\Str::limit($cDesc, 60) }}</div>
                        <button type="button" class="ffh-ticket-code" style="color:{{ $cColor }}" data-ffh-copy="{{ $promotion->title }}">{{ $promotion->title }} <i class="bi bi-copy" aria-hidden="true"></i></button>
                        @if ($cExp !== '')
                            <div class="ffh-ticket-exp">{{ $cExp }}</div>
                        @endif
                    </div>
                    <div class="ffh-ticket-stub" aria-hidden="true"><span class="ffh-barcode"></span></div>
                </div>
            @endforeach
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
