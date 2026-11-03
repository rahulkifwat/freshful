@extends('layout.master')
@push('styles')
<style>
    .ff-detail { max-width:1320px; margin:0 auto; padding:1.5rem 1rem; }
    .ff-detail-crumbs { color:#7b867e; font-size:.88rem; }
    .ff-detail-card { overflow:hidden; border:1px solid #ecefe9; border-radius:22px; background:#fff; box-shadow:0 12px 36px rgba(26,38,29,.07); }
    .ff-detail-photo { position:relative; display:grid; place-items:center; min-height:430px; padding:1.25rem; background:#f7f8f4; }
    .ff-detail-photo img { width:100%; max-height:510px; object-fit:contain; border-radius:16px; }
    .ff-detail-info { padding:clamp(1.25rem,3vw,2.5rem); }
    .ff-detail-category { display:inline-flex; align-items:center; padding:.38rem .7rem; border-radius:999px; background:#fff0eb; color:#d42d12; font-size:.78rem; font-weight:750; }
    .ff-detail-title { margin:.9rem 0 .35rem; color:#202923; font-size:clamp(1.6rem,3vw,2.5rem); font-weight:850; letter-spacing:-.04em; }
    .ff-detail-price { color:#151515; font-size:1.9rem; font-weight:850; }
    .ff-detail-old { color:#89928c; text-decoration:line-through; }
    .ff-detail-meta { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; }
    .ff-detail-meta-item { display:flex; align-items:center; gap:.65rem; padding:.75rem; border:1px solid #edf0e8; border-radius:13px; background:#fbfcf9; }
    .ff-detail-meta-item i { color:#f33a17; font-size:1.1rem; }
    .ff-detail-actions { display:flex; align-items:center; gap:.7rem; flex-wrap:wrap; margin-top:1.2rem; }
    .ff-detail-qty { display:flex; align-items:center; gap:.5rem; padding:.3rem; border:1px solid #e7ebe4; border-radius:999px; }
    .ff-detail-qty button { display:grid; place-items:center; width:34px; height:34px; border:0; border-radius:50%; background:#f3f5f1; color:#303a33; font-size:1.1rem; }
    .ff-detail-qty input { width:38px; padding:0; border:0; text-align:center; font-weight:800; }
    .ff-related { max-width:1440px; margin:0 auto; padding:1rem 20px 2.5rem; }
    .ff-related-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; }
    @media(max-width:1199px) { .ff-related-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media(max-width:767px) { .ff-related { padding:1rem 14px 2rem; } .ff-related-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; } .ff-detail-photo { min-height:280px; } .ff-detail-meta { gap:.45rem; } .ff-detail-meta-item { padding:.6rem; font-size:.85rem; } }
</style>
@endpush
@section('content')
@php
    $detailPrice = (float) ($product->main_price ?? 0);
    $detailMrp = (float) ($product->MRP ?? 0);
    $detailDiscount = $detailMrp > $detailPrice && $detailMrp > 0 ? (int) round((($detailMrp - $detailPrice) / $detailMrp) * 100) : 0;
@endphp
<main>
    <div class="ff-detail">
        <nav class="mb-3 ff-detail-crumbs" aria-label="Breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('view_product', ['category' => $product->category_id]) }}">{{ $product->category ?? 'Products' }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $product->title ?? $product->product_name ?? 'Product' }}</li>
            </ol>
        </nav>
        <section class="ff-detail-card">
            <div class="row g-0 align-items-stretch">
                <div class="col-lg-6"><div class="ff-detail-photo">
                    @if($detailDiscount > 0)<span class="badge rounded-pill text-bg-danger position-absolute top-0 start-0 m-3">Save {{ $detailDiscount }}%</span>@endif
                    <img src="{{ asset('uploads/images/products/' . $product->product_image) }}" alt="{{ $product->title ?? $product->product_name ?? 'Product image' }}">
                </div></div>
                <div class="col-lg-6"><div class="ff-detail-info h-100">
                    <span class="ff-detail-category"><i class="bi bi-stars me-1"></i>{{ $product->category ?? 'Freshful pick' }}</span>
                    <h1 class="ff-detail-title">{{ $product->title ?? $product->product_name ?? 'Freshful product' }}</h1>
                    <p class="text-muted mb-3">Fresh quality, carefully packed for your kitchen.</p>
                    <div class="mb-3"><span class="ff-detail-price">₹{{ number_format($detailPrice, 2) }}</span>@if($detailDiscount > 0)<span class="ff-detail-old ms-2">MRP ₹{{ number_format($detailMrp, 2) }}</span>@endif</div>
                    @if(filled($product->description ?? null))<p class="mb-4">{{ $product->description }}</p>@endif
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach (explode(',', (string)($product->property ?? '')) as $property)
                            @if (trim($property) !== '')<span class="feature-pill">{{ trim($property) }}</span>@endif
                        @endforeach
                    </div>
                    <div class="ff-detail-actions">
                        <div class="ff-detail-qty" aria-label="Choose quantity">
                            <button type="button" onclick="document.getElementById('detailQuantity').stepDown()" aria-label="Decrease quantity">−</button>
                            <input type="number" id="detailQuantity" value="1" min="1" inputmode="numeric" aria-label="Quantity">
                            <button type="button" onclick="document.getElementById('detailQuantity').stepUp()" aria-label="Increase quantity">+</button>
                        </div>
                        <button type="button" onclick="addSelectedProductQuantity({{ $product->id }})" class="btn btn-primary rounded-pill px-4 py-2 fw-bold"><i class="bi bi-basket2 me-1"></i>Add to Cart</button>
                        <button type="button" onclick="addProductToCompare({{ $product->id }})" class="btn btn-outline-secondary rounded-pill px-3 py-2"><i class="bi bi-arrow-left-right me-1"></i>Compare</button>
                    </div>
                    <hr class="my-3">
                    <div class="ff-detail-meta">
                        <div class="ff-detail-meta-item"><i class="bi bi-clock"></i><div><small class="text-muted d-block">Cooking time</small><strong>{{ $product->cooking_time ? $product->cooking_time.' mins' : '—' }}</strong></div></div>
                        <div class="ff-detail-meta-item"><i class="bi bi-people"></i><div><small class="text-muted d-block">Serves</small><strong>{{ $product->serves ?: '—' }}</strong></div></div>
                        <div class="ff-detail-meta-item"><i class="bi bi-speedometer2"></i><div><small class="text-muted d-block">Pack size</small><strong>{{ $product->gross_quantity }} {{ $product->product_unit }}</strong></div></div>
                        <div class="ff-detail-meta-item"><i class="bi bi-box-seam"></i><div><small class="text-muted d-block">Pieces</small><strong>{{ $product->pieces ?: '—' }}</strong></div></div>
                    </div>
                    <p class="small text-muted text-end mt-3 mb-0">Available in <strong>90 minutes</strong> and for <strong>Pre-Order</strong></p>
                </div></div>
            </div>
        </section>
    </div>
    @if(!empty($moreProduct) && count($moreProduct))
    <section class="ff-related" aria-labelledby="related-title">
        <div class="d-flex align-items-end justify-content-between gap-3 mb-3"><div><p class="small text-muted mb-1">MORE TO EXPLORE</p><h2 id="related-title" class="h2 mb-0">You may also like</h2></div><a class="text-decoration-none fw-semibold" href="{{ url('/view_product/all') }}">Shop all <i class="bi bi-arrow-right"></i></a></div>
        <div class="ff-product-grid ff-related-grid">
            @foreach ($moreProduct as $relatedProduct)
                @include('partials.home-product-card', ['product' => $relatedProduct])
            @endforeach
        </div>
    </section>
    @endif
</main>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded', function () { if (typeof syncProductQuantityControls === 'function') syncProductQuantityControls(); });</script>
@endpush
