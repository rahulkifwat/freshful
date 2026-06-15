@extends('layout.master')
@section('content')

<main class="py-5">
    <div class="container">
        <h2 class="fw-bold text-center mb-4">Compare Products</h2>

        @if(empty($products) || count($products) === 0)
            <div class="text-center py-5">
                <i class="bi bi-clipboard-x fs-1 text-muted"></i>
                <p class="mt-3 text-muted">No products selected for comparison.<br>Browse products and click "Compare" to add them here.</p>
                <a href="{{ route('product') }}" class="btn btn-primary mt-2">Browse Products</a>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-capitalize align-middle">
                <tbody>
                    <tr>
                        <th class="bg-light" style="width:160px;">Image</th>
                        @foreach($products as $p)
                        <td class="text-center">
                            <img src="{{ asset('uploads/images/products/'.$p->product_image) }}" alt="{{ $p->item }}" class="img-fluid" style="height:100px;width:150px;object-fit:cover;">
                        </td>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="bg-light">Title</th>
                        @foreach($products as $p)
                        <td>
                            <a href="{{ route('product_detail', ['id' => $p->id]) }}">{{ $p->item }}</a>
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
