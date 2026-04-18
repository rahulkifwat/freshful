@extends('layout.master')
@section('content')
<main>
    <!-- hero slider section  -->
    <div class="hero-slider">
        <div class="swiper heroSwiper">
            <!-- Additional required wrapper -->
            <div class="swiper-wrapper">
                <!-- Slides -->
                @foreach ($banners as $banner)
                    <div class="swiper-slide">
                        <a href="{{ url($banner->url) }}">
                            <img src="{{ asset('uploads/images/banners/'. $banner->image) }}" alt="Slider 1" class="img-fluid">
                        </a>
                    </div>
                @endforeach
            </div>
            <!-- If we need pagination -->
            <div class="swiper-pagination"></div>

            <!-- If we need navigation buttons -->
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>


        </div>
    </div>

    <!-- Shop by Category  -->
    <div class="shop-category-section py-sm-5 py-3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <h2 class="fs-2 fs-md-5  mb-sm-5 mb-2 ">Shop by Category?</h2>
                </div>
            </div>
            <div class="swiper categorySwiper py-sm-3 pt-2 pt-sm-0">
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

            </div>
        </div>
    </div>
    <!-- Why Freshful Section -->
    <div class="why-freshful-section pt-3 pt-sm-5 pb-3 pb-sm-5   my-sm-3" style="background-color: #fae4e482;">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <h2 class="fs-2 fs-sm-1 mb-1">Why Freshful?</h2>
                    <p class='fs-6 text-muted fw-normal mb-0 mb-sm-1'>Quality you can trust, freshness you can taste</p>
                </div>
            </div>
            <!-- <div class="d-flex flex-wrap justify-content-between gap-1 align-items-center mt-3"> -->


            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-lg-5 g-0 g-sm-3 mt-3">

                <div class="Freshi col d-flex justify-content-center flex-sm-row flex-column align-items-center gap-1">
                    <div class="Freshi-img">
                        <img src="{{ asset('assets/image/why/FreshlyCut.png') }}" alt="Freshly Cut" class="img-fluid">
                    </div>
                    <div class="Freshi-text">
                        <h6 class="fs-6 mb-0">Freshly Cut</h6>
                        <a href="{{ url('/why-freshful') }}" class="text-primary-alt">Know How</a>
                    </div>
                </div>
                <div class="Freshi col d-flex justify-content-center flex-sm-row flex-column align-items-center gap-1">
                    <div class="Freshi-img">
                        <img src="{{ asset('assets/image/why/FarmFreshEveryday.png') }}" alt="Freshly Cut" class="img-fluid">
                    </div>
                    <div class="Freshi-text">
                        <h6 class="fs-6 mb-0">Farm Fresh Everyday</h6>
                        <a href="{{ url('/why-freshful') }}" class="text-primary-alt">Know How</a>
                    </div>
                </div>
                <div class="Freshi col d-flex justify-content-center flex-sm-row flex-column align-items-center gap-1">
                    <div class="Freshi-img">
                        <img src="{{ asset('assets/image/why/SafeHealthy.png') }}" alt="Freshly Cut" class="img-fluid">
                    </div>
                    <div class="Freshi-text">
                        <h6 class="fs-6 mb-0">Safe & Healthy</h6>
                        <a href="{{ url('/why-freshful') }}" class="text-primary-alt">Know How</a>
                    </div>
                </div>
                <div class="Freshi col d-flex justify-content-center flex-sm-row flex-column align-items-center gap-1">
                    <div class="Freshi-img">
                        <img src="{{ asset('assets/image/why/AntibioticFree.png') }}" alt="Freshly Cut" class="img-fluid">
                    </div>
                    <div class="Freshi-text">
                        <h6 class="fs-6 mb-0">Antibiotic & Alcohol free</h6>
                        <a href="{{ url('/why-freshful') }}" class="text-primary-alt">Know How</a>
                    </div>
                </div>
                <div class="Freshi col d-flex justify-content-center flex-sm-row flex-column align-items-center gap-1">
                    <div class="Freshi-img">
                        <img src="{{ asset('assets/image/why/LocalyProduced.png') }}" alt="Freshly Cut" class="img-fluid">
                    </div>
                    <div class="Freshi-text">
                        <h6 class="fs-6 mb-0">Locally Produced</h6>
                        <a href="{{ url('/why-freshful') }}" class="text-primary-alt">Know How</a>
                    </div>
                </div>
            </div>


        </div>
    </div>

    <!-- Best Sellers Section -->
    <div class="best-sellers-section pt-3 pt-sm-5 pb-3 pb-sm-5 my-sm-3" style="background-image: url('{{ asset('assets/image/food-parallax.jpg') }}')">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <h2 class="fs-2 mb-1 text-white">Best Sellers</h2>
                </div>
            </div>
            <div class="swiper bestSellerSwiper py-3 px-2">
                <!-- Additional required wrapper -->
                <div class="swiper-wrapper">
                    <!-- Slides -->
                    @foreach ($bestSellers as $bestseller )
                        <div class="swiper-slide">
                            <div class="pro-card">
                                <div class="pro-card-img">
                                    <a href="{{ route('product_detail', ['id' => $bestseller->id]) }}">
                                        <img src="{{ asset('uploads/images/products/'. $bestseller->product_image) }}" alt="Product 1" class="img-fluid">
                                    </a>
                                </div>
                                <div class="pro-card-category-btn">
                                    <p class="text-white m-0">{{ $bestseller->category->name }}</p>
                                </div>
                                <div class="pro-card-item">
                                    <a href="{{ route('product_detail', ['id' => $bestseller->id]) }}" class="fs-5  fw-bold text-black w-full">
                                        {{ $bestseller->product_name }}
                                    </a>
                                    <p class="mb-0 text-black-50 fw-normal text-truncate   fs-6" style="max-width:350px;">{{ $bestseller->description }}</p>
                                    <div class="d-flex justify-content-start align-items-center gap-2 mt-2">
                                        <img src="{{ asset('assets/image/product/weight.png') }}" alt="weight" class="img-fluid pro-weight">
                                        <p class="text-muted fs-6 mb-0 text-body">{{ $bestseller->gross_quantity }} {{ $bestseller->product_unit }}</p>
                                    </div>

                                    <div class="pro-card-price d-flex justify-content-between align-items-end mt-4">
                                        <div class=" d-flex justify-content-center align-items-end gap-3">
                                            <div>
                                                <p class="text-muted fs-8 mb-0 text-black-50 text-decoration-line-through">₹ {{ $bestseller->main_price }}</p>
                                                <h4 class="fs-5 m-0 text-primary">MRP ₹ {{ $bestseller->cost_price }}</h4>
                                            </div>
                                            <h5 class="fs-5 mb-0 text-success">{{ $bestseller->off ?? 0 }}% off</h5>
                                        </div>
                                        <a href="javascript:void(0);" onclick="addToCart({{ $bestseller->id }})" class="btn btn-primary fs-8 fw-bold" style="border-radius: 5px;">Add to Cart</a>
                                    </div>
                                    <div class="pro-card-footer border-top py-1 mt-2 d-flex justify-content-center align-items-center gap-2">
                                        <img src="{{ asset('assets/image/product/delivery_boy.png') }}" alt="weight" class="img-fluid pro-delivery-boy">
                                        <p class="m-0 fs-6">Today 06PM - 07PM</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>

    </div>
    </div>
    <!-- Meat & Seafood Section -->
    <div class="meat-sellers-section pt-3 pt-sm-5 pb-3 pb-sm-5 my-sm-3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <h2 class="fs-2 mb-1">Meat & Seafood ( * Delivery In 40 Minutes)</h2>
                </div>
            </div>
            <div class="py-3">
                <!-- Additional required wrapper -->
                <div class="row ">
                    <!-- Slides -->
                    @foreach ($meatAndSeafoods as $ms)
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 mb-4">
                            <div class="pro-card">
                                <div class="pro-card-img">
                                    <a href="{{ route('product_detail', ['id' => $ms->id]) }}">
                                        <img src="{{ asset('uploads/images/products/'. $ms->product_image) }}" alt="Product 1" class="img-fluid">
                                    </a>
                                </div>
                                <div class="pro-card-category-btn">
                                    <p class="text-white m-0">{{ $ms->category->name }}</p>
                                </div>
                                <div class="pro-card-item">
                                    <a href="{{ route('product_detail', ['id' => $ms->id]) }}" class="fs-5  fw-bold text-black w-full">
                                        {{ $ms->product_name }}
                                    </a>
                                    <p class="mb-0 text-black-50 fw-normal text-truncate   fs-6" style="max-width:350px;">{{ $ms->description }}</p>
                                    <div class="d-flex justify-content-start align-items-center gap-2 mt-2">
                                        <img src="{{ asset('assets/image/product/weight.png') }}" alt="weight" class="img-fluid pro-weight">
                                        <p class="text-muted fs-6 mb-0 text-body">{{ $ms->gross_quantity }} {{ $ms->product_unit }}</p>
                                    </div>

                                    <div class="pro-card-price d-flex justify-content-between align-items-end mt-4">
                                        <div class=" d-flex justify-content-center align-items-end gap-3">
                                            <div>
                                                <p class="text-muted fs-8 mb-0 text-black-50 text-decoration-line-through">₹ {{ $ms->cost_price }}</p>
                                                <h4 class="fs-5 m-0 text-primary">MRP ₹ {{ $ms->main_price }}</h4>
                                            </div>
                                            <h5 class="fs-5 mb-0 text-success">{{ $ms->off ?? 0 }}% off</h5>
                                        </div>
                                        <a href="javascript:void(0);" onclick="addToCart({{ $ms->id }})"  style="border-radius: 5px;">Add to Cart</a>
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
                    <a href="{{ url('/view_product/all/Meat & Seafoods') }}" class="btn btn-primary-alt lh-2 pb-2 px-3" style="border-radius: 50px;">view more</a>
                </div>
            </div>
        </div>

    </div>
    </div>
    <!-- discount image section -->
    <div class="discount-image my-sm-3">
        <div class="container-fluid">
            <div class="row">
                @foreach ($coupons as $coupon)
                    <div class="col-sm-12 col-md-4 col-lg-4">
                        <div class="discount-card">
                            <img src="{{ asset('assets/image/discount/'.$coupon->image) }}" alt="Discount" class="img-fluid">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    <!-- shop by category section -->
    <div class="cat-images-section my-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <h2 class="fs-2 mb-1">Shop By Category</h2>
                </div>
            </div>
            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 mt-3">
                @foreach ($shopByCategory as $sc )
                    <div class="col">
                        <div class="cat-card text-center">
                            <a href="{{ route('product', [$sc->id]) }}">
                                <img src="{{ asset('assets/image/other/'.$sc->image) }}" alt="Discount" class="img-fluid ">
                            </a>
                            <h6 class="mb-0 fs-5 mt-2 fw-bold">{{ $sc->name }}</h6>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <!-- uniqid image section -->
    <div class="unique-images-section my-5">
        <div class="container-fluid">
            <div class="row g-3 g-sm-3 g-md-3 g-lg-3">
                <div class="col-sm-12 col-md-4 col-lg-4">
                    <div class="unique-card">
                        <img src="{{ asset('assets/image/other/2.png') }}" alt="Discount" class="img-fluid">
                    </div>
                </div>
                <div class="col-sm-12 col-md-4 col-lg-4">
                    <div class="unique-card">
                        <img src="{{ asset('assets/image/other/3.png') }}" alt="Discount" class="img-fluid">
                    </div>
                </div>
                <div class="col-sm-12 col-md-4 col-lg-4">
                    <div class="unique-card">
                        <img src="{{ asset('assets/image/other/Fl5od1667877499.png') }}" alt="Discount" class="img-fluid">
                    </div>
                </div>



            </div>
        </div>

    </div>

    <!-- our app section  -->
    <div class="ourapp-section">
        <div class="container-fluid  ">
            <div class="our-app-section  ">
                <div class="container">
                    <div class="row align-items-center g-3 g-sm-3 g-md-3 g-lg-3">
                        <div class="col-sm-12 col-md-6 col-lg-7">
                            <h1 class="our-app-text fw-bold text-left">Enjoy your best <br />
                                Experience with our app</h3>
                                <img src="{{ asset('assets/image/other/app_stroe.png') }}" alt="app_stroe" class="img-fluid app_stroe mt-sm-3 mt-md-5 mt-lg-5">
                        </div>
                        <div class="col-sm-12 col-md-6 col-lg-5">

                            <div class="our-app-images-card">
                                <img src="{{ asset('assets/image/other/Apple-iPhone-11-PNG-Image.png') }}" alt="Discount" class="img-fluid our-app-images">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- SUBSCRIBE OUR  -->

    <div class="subscribe-section bg-primary py-3">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">

                <h4 class="text-white text-upparcase mb-0   ">SUBSCRIBE OUR NEWSLETTER FOR GET <br /> UPDATED WITH ALL NEW PRODUCT</h4>
                <form class="d-flex justify-content-center align-items-center w-sm-100 w-md-50">
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