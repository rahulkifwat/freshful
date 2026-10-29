@extends('layout.master')

@section('title', 'Shop by category | Freshful')
@section('meta_description', 'Browse every Freshful category and choose the products you need for your next meal.')

@push('styles')
<style>
    .ff-categories-page { --cat-green:#176b45; --cat-ink:#202923; color:var(--cat-ink); }
    .ff-categories-wrap { max-width:1440px; margin:0 auto; padding:clamp(1.5rem,4vw,3rem) 1rem; }
    .ff-categories-heading { margin-bottom:1.5rem; }
    .ff-categories-heading h1 { margin:0; font-size:clamp(1.8rem,3vw,2.6rem); font-weight:800; letter-spacing:-.04em; }
    .ff-categories-heading p { margin:.5rem 0 0; color:#647168; }
    .ff-all-category-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:1rem; }
    .ff-all-category { display:flex; align-items:center; gap:1rem; min-width:0; padding:1rem; border:1px solid #edf0e8; border-radius:18px; background:#fff; color:var(--cat-ink)!important; text-decoration:none; box-shadow:0 5px 18px rgba(31,49,37,.04); transition:transform .2s,box-shadow .2s,border-color .2s; }
    .ff-all-category:hover { transform:translateY(-3px); border-color:#c8dfbf; box-shadow:0 12px 26px rgba(36,67,43,.1); }
    .ff-all-category img { flex:none; width:76px; height:76px; border-radius:50%; object-fit:cover; background:#f7f8f2; }
    .ff-all-category span { min-width:0; font-weight:750; }
    @media(max-width:991px) { .ff-all-category-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media(max-width:575px) { .ff-all-category-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; } .ff-all-category { flex-direction:column; align-items:flex-start; gap:.65rem; padding:.8rem; border-radius:15px; } .ff-all-category img { width:60px; height:60px; } .ff-all-category span { font-size:.85rem; } }
</style>
@endpush

@section('content')
<main class="ff-categories-page">
    <div class="ff-categories-wrap">
        <div class="ff-categories-heading">
            <a href="{{ route('home') }}" class="text-decoration-none fw-semibold" style="color:#176b45"><i class="bi bi-arrow-left me-1"></i> Back to Freshful</a>
            <h1 class="mt-3">Shop by category</h1>
            <p>Choose a category to browse all its products.</p>
        </div>
        <div class="ff-all-category-grid">
            @forelse($categories as $category)
                <a class="ff-all-category" href="{{ url('/view_product/'.$category->id) }}">
                    <img src="{{ asset('uploads/images/'.$category->circle_image) }}" alt="" loading="lazy">
                    <span>{{ $category->name }}</span>
                    <i class="bi bi-arrow-right ms-auto" aria-hidden="true"></i>
                </a>
            @empty
                <p class="text-muted">Categories are being updated. Please check again soon.</p>
            @endforelse
        </div>
    </div>
</main>
@endsection
