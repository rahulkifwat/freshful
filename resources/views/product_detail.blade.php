@extends('layout.master')
@section('content')
<style>
    .product-item{
        transition: all 0.3s ease;
    }
</style>
<main>



    <div class="container-fluid py-3">

        <!-- Breadcrumb -->
        <nav class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('view_product', ['category' => $product->category_id]) }}">{{ $product->category }}</a></li>
                <li class="breadcrumb-item active">{{ $product->product_name }}</li>
            </ol>
        </nav>
        <div class="info-box  shadow-sm">


            <div class="row g-4">

                <!-- Product Image -->
                <div class="col-lg-6">
                    <div class="position-relative product-image shadow-sm">
                        <span class="badge bg-danger product-badge">{{ $product->off ?? 0 }}% OFF</span>
                        <img src="{{ asset('uploads/images/products/' . $product->product_image) }}"
                            class="img-fluid w-100 h-100"
                            alt="Cabbage">
                    </div>
                </div>

                <!-- Product Info -->
                <div class="col-lg-6">
                    <div class="h-100">

                        <h2 class="fw-bold mb-2">{{ $product->product_name }}</h2>
                        <p class="text-muted mb-3">Organic • Farm Fresh • Premium Quality</p>

                        <div class="mb-3">
                            <span class="price"><span class="fs-6">M.R.P</span> ₹{{ $product->cost_price }} </span>
                            <span class="old-price ms-2">₹{{ $product->MRP }}</span>
                        </div>

                        <p class="mb-4">
                            {{ $product->description }}
                        </p>

                        <!-- Highlights -->
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach (explode(',', $product->property) as $property)
                                <span class="feature-pill">{{ trim($property) }}</span>
                            @endforeach
                        </div>

                        <!-- Quantity + Actions -->
                        <div class="row align-items-center g-3">
                            <div class="col-md-4">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" value="1" min="1">
                            </div>
                            <div class="col-md-4 d-grid gap-2">
                                <label class="form-label d-none d-sm-block">&nbsp;</label>
                                <button onclick="addToCart({{ $product->id }})"  class="btn btn-danger  ">Add to Cart</button>
                            </div>
                            <div class="col-md-4 d-grid gap-2">
                                <label class="form-label d-none d-sm-block">&nbsp;</label>
                                <button class="btn btn-outline-secondary ">Add to Compare</button>
                            </div>
                        </div>
                        <hr class="my-3">

                        <!-- Extra Info -->
                        <div class="row text-left">
                            <div class="col text-center">
                                <small class="text-muted d-block">Cooking Time</small>
                                <strong>{{ $product->cooking_time }} mins</strong>
                            </div>
                            <div class="col text-center">
                                <small class="text-muted d-block">Service</small>
                                <strong>{{ $product->serves }}</strong>
                            </div>
                            <div class="col text-center">
                                <small class="text-muted d-block">Net Weight</small>
                                <strong>{{ $product->unit_quantity }} {{ $product->product_unit }}</strong>
                            </div>
                            <div class="col text-center">
                                <small class="text-muted d-block">Pieces</small>
                                <strong>{{ $product->pieces }}</strong>
                            </div>
                        </div>
                        <hr class="my-3">


                        <div class="w-100 text-end mt-2">
                            <p class="fs-7 text-end text-muted mb-0">Available in <b>90 minutes</b> and for <b>Pre-Order</b> </p>
                        </div>


                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- Best Sellers Section -->
    <div class="best-sellers-section pt-5 pb-5   my-3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <h2 class="fs-2 mb-1">You may also like</h2>
                </div>
            </div>
            <div class="py-3">
                <!-- Additional required wrapper -->
                <div class="row ">
                    <!-- Slides -->
                    @foreach ($moreProduct as $moreProduct)
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-4 mb-4 product-item">
                            <div class="pro-card">
                                <div class="pro-card-img">
                                    <a href="{{ route('product_detail', ['id' => $moreProduct->id]) }}">
                                        <img src="{{ asset('uploads/images/products/'. $moreProduct->product_image) }}" alt="Product 1" class="img-fluid">
                                    </a>
                                </div>
                                <div class="pro-card-category-btn">
                                    <p class="text-white m-0">{{ $moreProduct->category }}</p>
                                </div>
                                <div class="pro-card-item">
                                    <a href="{{ route('product_detail', ['id' => $moreProduct->id]) }}" class="fs-5  fw-bold text-black w-full">
                                        {{ $moreProduct->product_name }}
                                    </a>
                                    <p class="mb-0 text-black-50 fw-normal text-truncate   fs-6" style="max-width:350px;">{{ $moreProduct->description }}</p>
                                    <div class="d-flex justify-content-start align-items-center gap-2 mt-2">
                                        <img src="{{ asset('assets/image/product/weight.png') }}" alt="weight" class="img-fluid pro-weight">
                                        <p class="text-muted fs-6 mb-0 text-body">{{ $moreProduct->gross_quantity }} {{ $moreProduct->product_unit }}</p>
                                    </div>

                                    <div class="pro-card-price d-flex justify-content-between align-items-end mt-4">
                                        <div class=" d-flex justify-content-center align-items-end gap-3">
                                            <div>
                                                <p class="text-muted fs-8 mb-0 text-black-50 text-decoration-line-through">₹ {{ $moreProduct->cost_price }}</p>
                                                <h4 class="fs-5 m-0 text-primary">MRP ₹ {{ $moreProduct->main_price }}</h4>
                                            </div>
                                            <h5 class="fs-5 mb-0 text-success">{{ $moreProduct->off ?? 0 }}% off</h5>
                                        </div>
                                        <a href="javascript:void(0);" onclick="addToCart({{ $moreProduct->id }})"  class="btn btn-primary fs-8 fw-bold" style="border-radius: 5px;">Add to Cart</a>
                                    </div>
                                    <div class="pro-card-footer border-top py-1 mt-2 d-flex justify-content-center align-items-center gap-2">
                                        <img src="{{ asset('assets/image/product/delivery_boy.png') }}" alt="weight" class="img-fluid pro-delivery-boy">
                                        <p class="m-0 fs-6">Today 06PM - 07PM</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                <div class="d-flex justify-content-center align-items-center mt-3">
                    <button id="loadMoreBtn" class="btn btn-primary-alt lh-2 pb-2 px-3" style="border-radius: 50px;">view more</button>
                </div>
            </div>
        </div>

    </div>
    </div>


</main>

@endsection
@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {

        let products = document.querySelectorAll(".product-item");
        let loadBtn = document.getElementById("loadMoreBtn");

        let itemsToShow = 6;
        let currentItems = 6;

        // hide products after 6
        products.forEach((item, index) => {
            if (index >= itemsToShow) {
                item.style.display = "none";
            }
        });

        loadBtn.addEventListener("click", function () {

            let items = [...products].slice(currentItems, currentItems + itemsToShow);

            items.forEach(el => {
                el.style.display = "block";
            });

            currentItems += itemsToShow;

            // hide button if no more products
            if (currentItems >= products.length) {
                loadBtn.style.display = "none";
            }

        });

    });
</script>
    
@endpush