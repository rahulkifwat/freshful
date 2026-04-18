@extends('layout.master')
@section('content')
<style>
    .product-item{
        transition: all 0.3s ease;
    }
</style>
<main>

    <!-- Shop by Category  -->
    <div class="shop-category-section bg-white py-sm-5 py-3">
        <div class="container-fluid">

            <div class="swiper viewProductSwiper py-3">
                <!-- Additional required wrapper -->
                <div class="swiper-wrapper">
                    <!-- Slides -->
                    @foreach ($categories as $category)
                        <div class="swiper-slide">
                            <a href="{{ url('/view_product/' . $category->id) }}" class="category-view">
                                <img src="{{ asset('uploads/images/'. $category->circle_image) }}" alt="Slider 1" class="img-fluid">
                                <p class="text-center">{{ $category->name }}</p>
                            </a>
                        </div>
                    @endforeach
                </div>


                <!-- If we need navigation buttons -->
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </div>
    </div>



    <!-- Meat & Seafood Section -->
    <div class="best-sellers-section    my-3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <h2 class="fs-2 mb-1">All Products</h2>
                </div>
            </div>
            <div class="py-3">
                <!-- Additional required wrapper -->
                <div class="row ">
                    <!-- Slides -->
                    @foreach ($products as $product)
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-4 mb-4 product-item">
                            <div class="pro-card">
                                <div class="pro-card-img">
                                    <a href="{{ route('product_detail', ['id' => $product->id]) }}">
                                        <img src="{{ asset('uploads/images/products/'. $product->product_image) }}" alt="Product 1" class="img-fluid">
                                    </a>
                                </div>
                                <div class="pro-card-category-btn">
                                    <p class="text-white m-0">{{ $product->category->name }}</p>
                                </div>
                                <div class="pro-card-item">
                                    <a href="{{ route('product_detail', ['id' => $product->id]) }}" class="fs-5  fw-bold text-black w-full">
                                        {{ $product->product_name }}
                                    </a>
                                    <p class="mb-0 text-black-50 fw-normal text-truncate   fs-6" style="max-width:350px;">{{ $product->description }}</p>
                                    <div class="d-flex justify-content-start align-items-center gap-2 mt-2">
                                        <img src="{{ asset('assets/image/product/weight.png') }}" alt="weight" class="img-fluid pro-weight">
                                        <p class="text-muted fs-6 mb-0 text-body">{{ $product->gross_quantity }} {{ $product->product_unit }}</p>
                                    </div>

                                    <div class="pro-card-price d-flex justify-content-between align-items-end mt-4">
                                        <div class=" d-flex justify-content-center align-items-end gap-3">
                                            <div>
                                                <p class="text-muted fs-8 mb-0 text-black-50 text-decoration-line-through">₹ {{ $product->cost_price }}</p>
                                                <h4 class="fs-5 m-0 text-primary">MRP ₹ {{ $product->main_price }}</h4>
                                            </div>
                                            <h5 class="fs-5 mb-0 text-success">{{ $product->off ?? 0 }}% off</h5>
                                        </div>
                                        <a href="javascript:void(0);" onclick="addToCart({{ $product->id }})"  class="btn btn-primary fs-8 fw-bold" style="border-radius: 5px;">Add to Cart</a>
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


    <!-- SUBSCRIBE OUR  -->

    <div class="subscribe-section bg-primary py-3">
        <div class="container">
            <div class="d-flex flex-row justify-content-between align-items-center">
                <h4 class="text-white text-upparcase mb-0   ">SUBSCRIBE OUR NEWSLETTER FOR GET <br /> UPDATED WITH ALL NEW PRODUCT</h4>
                <form class="d-flex justify-content-center align-items-center w-50">
                    <div class="input-group ">

                        <!-- Email input -->
                        <input
                            type="email"
                            class="form-control py-3 rounded-0 ps-4"
                            placeholder="Enter your email"
                            aria-label="Subscriber email"
                            required>

                        <!-- Send button with icon -->
                        <button class="btn btn-theme rounded-0 px-5" type="submit">
                            <i class="bi bi-send text-white fs-3"></i>
                        </button>

                    </div>
                </form>

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

        let itemsToShow = 9;
        let currentItems = 9;

        // hide products after 9
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