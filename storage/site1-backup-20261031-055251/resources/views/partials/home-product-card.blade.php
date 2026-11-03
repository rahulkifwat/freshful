@php
    $name = $product->product_name ?? $product->title ?? $product->item ?? 'Freshful product';
    $salePrice = (float) ($product->main_price ?? 0);
    $mrp = (float) ($product->MRP ?? 0);
    $hasDiscount = $mrp > $salePrice && $mrp > 0;
    $saving = $hasDiscount ? $mrp - $salePrice : 0;
    $unit = trim((string) (($product->gross_quantity ?? '').' '.($product->product_unit ?? '')));
    $productTags = array_map('trim', explode(',', (string) ($product->tag ?? '')));
    $antibioticFree = in_array('Antibiotic Free', $productTags, true);
    $unitIcon = str_contains(strtolower((string) ($product->product_unit ?? '')), 'pc') ? 'bi-box-seam' : 'bi-speedometer2';
@endphp
<article class="ff-product-card">
    <a class="ff-product-photo" href="{{ route('product_detail', ['id' => $product->id]) }}" aria-label="View {{ $name }}">
        <img src="{{ asset('uploads/images/products/'.$product->product_image) }}" alt="{{ $name }}" loading="lazy">
        @if ($hasDiscount)
            <span class="ff-product-savings">₹{{ number_format($saving, 0) }} Off</span>
        @endif
        @if ($antibioticFree)
            <span class="ff-product-quality">Antibiotic Free</span>
        @endif
    </a>
    <div class="ff-product-info">
        <a class="ff-product-name" href="{{ route('product_detail', ['id' => $product->id]) }}">{{ $name }}</a>
        @if ($unit !== '')
            <span class="ff-product-unit"><i class="bi {{ $unitIcon }} me-1 text-danger" aria-hidden="true"></i>{{ $unit }}</span>
        @endif
        <div class="ff-product-bottom">
            <div class="ff-price">
                ₹{{ number_format($salePrice, 2) }}
                @if ($hasDiscount)
                    <small class="ff-old-price">MRP ₹{{ number_format($mrp, 2) }}</small>
                @endif
            </div>
            <div class="ff-product-actions">
                <button type="button" class="ff-compare-button" onclick="addProductToCompare({{ $product->id }})" aria-label="Compare {{ $name }}" title="Add to comparison"><i class="bi bi-arrow-left-right"></i></button>
                <div class="ff-quantity-control" data-product-quantity="{{ $product->id }}">
                    <button type="button" class="ff-add product-add-button" onclick="addToCart({{ $product->id }})">Add</button>
                    <div class="ff-quantity-stepper" hidden aria-label="Change quantity for {{ $name }}">
                        <button type="button" class="ff-quantity-button" onclick="changeProductQuantity({{ $product->id }}, -1)" aria-label="Decrease quantity">−</button>
                        <span class="ff-quantity-value" aria-live="polite">0</span>
                        <button type="button" class="ff-quantity-button" onclick="changeProductQuantity({{ $product->id }}, 1)" aria-label="Increase quantity">+</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>
