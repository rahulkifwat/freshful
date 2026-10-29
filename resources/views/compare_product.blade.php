@extends('layout.master')
@push('styles')
<style>
    .ff-compare-page { max-width:1400px; margin:0 auto; padding:clamp(1.5rem,4vw,3rem) 1rem; }
    .ff-compare-heading { margin-bottom:1.5rem; }
    .ff-compare-heading h1 { margin:0; font-size:clamp(1.7rem,3vw,2.4rem); font-weight:850; letter-spacing:-.04em; }
    .ff-compare-table { overflow:hidden; border:1px solid #ecefe9; border-radius:18px; background:#fff; box-shadow:0 10px 30px rgba(26,38,29,.06); }
    .ff-compare-table table { margin:0; }
    .ff-compare-table th { width:165px; color:#465249; background:#f7f8f4!important; }
    .ff-compare-table td,.ff-compare-table th { padding:1rem; border-color:#edf0e8; }
    .ff-compare-table img { width:150px; height:125px; border-radius:12px; object-fit:cover; }
    .ff-compare-heading > div { flex:1; }
    .ff-compare-page .btn-outline-danger { border-color:#ffd0c2; color:#cf3216; }
    .ff-compare-page .btn-outline-danger:hover { background:linear-gradient(110deg,#F70202,#FF5500); color:#fff; }
    .ff-compare-page .table { --bs-table-striped-bg:#fff9f6; }
</style>
@endpush
@section('content')

<main class="ff-compare-page">
    <div>
        <div class="ff-compare-heading d-flex flex-wrap align-items-end justify-content-between gap-3"><div><p class="small text-muted mb-1">FRESHFUL PRODUCT TOOLS</p><h1>Compare products</h1><p class="text-muted mt-2 mb-0">Compare price, pack size and product details side by side.</p></div>@if(!empty($products) && count($products) > 0)<button type="button" class="btn btn-outline-danger rounded-pill px-3" onclick="clearProductCompare()"><i class="bi bi-trash3 me-1"></i>Clear comparison</button>@endif</div>

        @if(empty($products) || count($products) === 0)
            <div class="text-center py-5">
                <i class="bi bi-clipboard-x fs-1 text-muted"></i>
                <p class="mt-3 text-muted">No products selected for comparison.<br>Browse products and click "Compare" to add them here.</p>
                <a href="{{ route('product') }}" class="btn btn-primary mt-2">Browse Products</a>
            </div>
        @else
        <div class="table-responsive ff-compare-table">
            <table class="table table-bordered text-capitalize align-middle">
                <tbody>
                    <tr>
                        <th class="bg-light" style="width:160px;">Image</th>
                        @foreach($products as $p)
                        <td class="text-center">
                            <img src="{{ asset('uploads/images/products/'.$p->product_image) }}" alt="{{ $p->title ?? $p->item ?? 'Product' }}" class="img-fluid" style="height:100px;width:150px;object-fit:cover;">
                            <button type="button" class="btn btn-sm btn-link text-danger d-block mx-auto mt-2" onclick="removeProductFromCompare({{ $p->id }})"><i class="bi bi-x-circle me-1"></i>Remove</button>
                        </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-light">Title</th>
                        @foreach($products as $p)
                        <td>
                            <a href="{{ route('product_detail', ['id' => $p->id]) }}">{{ $p->title ?? $p->item ?? 'Freshful product' }}</a>
                        </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-light">MRP</th>
                        @foreach($products as $p)
                        <td>₹{{ $p->MRP }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-light">Offer Price</th>
                        @foreach($products as $p)
                        <td class="text-danger fw-bold">₹{{ $p->main_price }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-light">Unit Quantity</th>
                        @foreach($products as $p)
                        <td>{{ $p->unit_quantity }} {{ $p->product_unit }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-light">Gross Quantity</th>
                        @foreach($products as $p)
                        <td>{{ $p->gross_quantity }} {{ $p->product_unit }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-light">Pieces</th>
                        @foreach($products as $p)
                        <td>{{ $p->pieces ?? '-' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-light">Property</th>
                        @foreach($products as $p)
                        <td>{{ $p->property ?? '-' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-light">Serves</th>
                        @foreach($products as $p)
                        <td>{{ $p->serves ?? '-' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-light">Cooking Time</th>
                        @foreach($products as $p)
                        <td>{{ $p->cooking_time ? $p->cooking_time.' Min' : '-' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-light">Description</th>
                        @foreach($products as $p)
                        <td>{{ $p->description ?? '-' }}</td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-light">Action</th>
                        @foreach($products as $p)
                        <td class="text-center">
                            <button class="btn btn-primary btn-sm" onclick="addToCart({{ $p->id }})">Add to Cart</button>
                        </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
        @endif
    </div>
</main>

@endsection
