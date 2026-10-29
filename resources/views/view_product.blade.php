@extends('layout.master')
@section('content')
<style>
    .freshful-catalog { --ff-green:#176b45; --ff-green-dark:#0e4b32; --ff-ink:#202923; --ff-muted:#647168; max-width:1440px; margin:0 auto; padding:0 20px; color:var(--ff-ink); }
    .freshful-catalog .ff-section { padding:clamp(1.5rem,3vw,2.5rem) 0; }
    .freshful-catalog .ff-section-heading { display:flex; align-items:end; justify-content:space-between; gap:1rem; margin-bottom:1.2rem; }
    .freshful-catalog .ff-section-heading h2 { margin:0; font-size:clamp(1.5rem,2.5vw,2.1rem); font-weight:800; letter-spacing:-.03em; }
    .freshful-catalog .ff-section-heading p { margin:.35rem 0 0; color:var(--ff-muted); }
    .freshful-catalog .ff-category-grid { display:flex; gap:.8rem; overflow:auto hidden; scroll-snap-type:x mandatory; scroll-behavior:smooth; scrollbar-width:none; padding:.15rem .1rem .65rem; }
    .freshful-catalog .ff-category-grid::-webkit-scrollbar { display:none; }
    .freshful-catalog .ff-category { flex:0 0 clamp(112px,12vw,154px); min-width:0; padding:1rem .65rem .85rem; border:1px solid #edf0e8; border-radius:20px; color:var(--ff-ink)!important; text-align:center; text-decoration:none; background:#fff; box-shadow:0 4px 14px rgba(31,49,37,.035); scroll-snap-align:start; transition:transform .2s,box-shadow .2s,border-color .2s; }
    .freshful-catalog .ff-category:hover { transform:translateY(-4px); border-color:#c8dfbf; box-shadow:0 12px 28px rgba(36,67,43,.09); }
    .freshful-catalog .ff-category img { display:block; width:clamp(58px,6.5vw,88px); height:clamp(58px,6.5vw,88px); margin:0 auto .7rem; border-radius:50%; object-fit:cover; background:#f7f8f2; }
    .freshful-catalog .ff-category span { display:block; font-size:.9rem; font-weight:700; line-height:1.25; }
    .freshful-catalog .ff-category-controls { display:flex; align-items:center; gap:.5rem; }
    .freshful-catalog .ff-scroll-arrow { display:grid; place-items:center; width:36px; height:36px; border:1px solid #e8ebe5; border-radius:50%; background:#fff; color:#252a26; transition:all .2s; }
    .freshful-catalog .ff-scroll-arrow:hover { border-color:#ffb8a4; color:#e73816; box-shadow:0 4px 12px rgba(247,2,2,.1); }
    .freshful-catalog .catalog-products { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; }
    @media(max-width:1199px) { .freshful-catalog .catalog-products { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media(max-width:767px) { .freshful-catalog { padding:0 14px; } .freshful-catalog .catalog-products { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; } .freshful-catalog .ff-section-heading { align-items:flex-start; } .freshful-catalog .ff-category { padding:.7rem .45rem; border-radius:15px; } .freshful-catalog .ff-category img { width:58px; height:58px; } .freshful-catalog .ff-category span { font-size:.78rem; } }
</style>
<main class="freshful-catalog">
    <section class="ff-section" aria-labelledby="catalog-categories-title">
        <div class="ff-section-heading"><div><h2 id="catalog-categories-title">Shop by category</h2><p>Browse fresh picks by category.</p></div><div class="ff-category-controls"><button class="ff-scroll-arrow" type="button" data-scroll-target="catalog-category-track" data-scroll-direction="-1" aria-label="Previous categories"><i class="bi bi-chevron-left"></i></button><button class="ff-scroll-arrow" type="button" data-scroll-target="catalog-category-track" data-scroll-direction="1" aria-label="Next categories"><i class="bi bi-chevron-right"></i></button><a href="{{ route('categories') }}" class="text-decoration-none fw-semibold text-nowrap">View all <i class="bi bi-arrow-right"></i></a></div></div>
        <div class="ff-category-grid" id="catalog-category-track">
            @foreach ($categories->unique('name') as $category)
                <a href="{{ url('/view_product/' . $category->id) }}" class="ff-category"><img src="{{ asset('uploads/images/'. $category->circle_image) }}" alt="{{ $category->name }}" loading="lazy"><span>{{ $category->name }}</span></a>
            @endforeach
        </div>
    </section>
    <section class="ff-section pt-0" aria-labelledby="catalog-title">
        <div class="ff-section-heading"><div><h1 id="catalog-title">{{ $cat->name ?? 'All Products' }}</h1><p>Fresh picks for your kitchen.</p></div><span class="small text-muted">{{ $products->count() }} items</span></div>
        <div class="catalog-products">
            @forelse ($products as $product)
                @include('partials.home-product-card', ['product' => $product])
            @empty
                <div class="text-center text-muted py-5" style="grid-column:1/-1;">No products are available in this category right now.</div>
            @endforelse
        </div>
    </section>
</main>
@endsection
@push('scripts')
<script>window.cartQuantities = @json($cartQuantities);</script>
@endpush
