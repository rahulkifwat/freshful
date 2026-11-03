@php
    $sfName = $product->product_name ?? 'Freshful product';
    $sfPrice = (float) ($product->main_price ?? 0);
    $sfMrp = (float) ($product->MRP ?? 0);
    $sfSave = ($sfMrp > $sfPrice && $sfMrp > 0) ? $sfMrp - $sfPrice : 0;
    $sfUnit = trim((string) (($product->gross_quantity ?? '').' '.($product->product_unit ?? '')));
    $sfPieces = trim((string) ($product->pieces ?? ''));
    $sfWeight = implode(' · ', array_filter([$sfUnit, $sfPieces !== '' ? $sfPieces.' Pieces' : '']));
    $sfTags = \App\Services\Storefront\HomeV2Data::tagsOf($product);
    $sfRibbon = $sfTags['chips'][0] ?? null;
    $sfMoreTags = array_slice($sfTags['chips'], 1);
    $sfStock = $stock[$product->id] ?? null;
    $sfOut = $sfStock && $sfStock['out'];
    $sfUrl = route('product_detail', ['id' => $product->id]);
@endphp
<article class="ffv-card{{ $sfOut ? ' is-out' : '' }}">
    <a class="ffv-photo" href="{{ $sfUrl }}" aria-label="View {{ $sfName }}">
        <img src="{{ asset('uploads/images/products/'.$product->product_image) }}" alt="{{ $sfName }}" loading="lazy" onerror="this.style.visibility='hidden'">
        @if ($sfOut)
            <span class="ffv-out-band">Out of Stock</span>
        @else
            @if ($sfSave > 0)
                <span class="ffv-off">{{ \App\Services\Storefront\HomeV2Data::money($sfSave) }} Off</span>
            @endif
            @if ($sfRibbon)
                <span class="ffv-ribbon" style="background:{{ \App\Services\Storefront\HomeV2Data::ribbonColor($sfRibbon) }}">{{ $sfRibbon }}</span>
            @endif
        @endif
        @foreach ($sfTags['photo'] as $sfLabel)
            <span class="ffv-photo-label">{{ $sfLabel }}</span>
        @endforeach
    </a>
    <div class="ffv-body">
        @php
            $sfShowFresh = ! $sfOut && $sfStock && $sfStock['fresh'];
            $sfShowLow = ! $sfOut && $sfStock && $sfStock['low'];
            $sfChipTags = $sfOut ? $sfTags['chips'] : $sfMoreTags;
        @endphp
        @if ($sfShowFresh || $sfShowLow || count($sfChipTags))
            <div class="ffv-chips">
                @if ($sfShowFresh)
                    <span class="ffv-chip ffv-chip-fresh">{{ $sfStock['freshLabel'] }}</span>
                @endif
                @if ($sfShowLow)
                    <span class="ffv-chip ffv-chip-low">Only {{ $sfStock['left'] }} left</span>
                @endif
                @foreach ($sfChipTags as $sfTag)
                    <span class="ffv-chip" style="{{ \App\Services\Storefront\HomeV2Data::tagStyle($sfTag) }}">{{ $sfTag }}</span>
                @endforeach
            </div>
        @endif
        <a class="ffv-name" href="{{ $sfUrl }}">{{ $sfName }}</a>
        @if ($sfWeight !== '')
            <div class="ffv-weight">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="5.5" r="2.5"/><path d="M7 9h10l2.5 11h-15L7 9z"/></svg>
                <span>{{ $sfWeight }}</span>
            </div>
        @endif

        @if ($sfOut)
            <div class="ffv-foot ffv-foot-out">
                <div class="ffv-price">{{ \App\Services\Storefront\HomeV2Data::money($sfPrice) }}</div>
                <div class="ffv-notify-row">
                    <span class="ffv-notify-text">Get notified when available</span>
                    <button type="button" class="ffv-notify" data-stock-alert="{{ $product->id }}">Notify
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 22a2.5 2.5 0 0 0 2.4-2h-4.8a2.5 2.5 0 0 0 2.4 2zm7-6V11a7 7 0 0 0-5.5-6.8V3.5a1.5 1.5 0 0 0-3 0v.7A7 7 0 0 0 5 11v5l-2 2v1h18v-1l-2-2z"/></svg>
                    </button>
                </div>
            </div>
        @else
            <div class="ffv-foot">
                <div class="ffv-prices">
                    @if ($sfSave > 0)
                        <div class="ffv-mrp"><s>{{ \App\Services\Storefront\HomeV2Data::money($sfMrp) }}</s><small>MRP</small></div>
                    @endif
                    <div class="ffv-price">{{ \App\Services\Storefront\HomeV2Data::money($sfPrice) }}</div>
                </div>
                <div class="ff-product-actions">
                    <div class="ff-quantity-control" data-product-quantity="{{ $product->id }}">
                        <button type="button" class="ff-add product-add-button" onclick="addToCart({{ $product->id }})">Add</button>
                        <div class="ff-quantity-stepper" hidden aria-label="Change quantity for {{ $sfName }}">
                            <button type="button" class="ff-quantity-button" onclick="changeProductQuantity({{ $product->id }}, -1)" aria-label="Decrease quantity">&minus;</button>
                            <span class="ff-quantity-value" aria-live="polite">0</span>
                            <button type="button" class="ff-quantity-button" onclick="changeProductQuantity({{ $product->id }}, 1)" aria-label="Increase quantity">+</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</article>
