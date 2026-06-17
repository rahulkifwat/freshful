<nav class="navbar navbar-expand-lg navbar-light top-header">
    <div class="container-fluid">
        <ul class="navbar-nav flex-row gap-3">
            <li class="nav-item">
                <a class="nav-link active text-white fs-sm-6 fs-8" aria-current="page" href="{{ url('/') }}">
                    <i class="bi bi-telephone-fill"></i>
                    +91 8124200242</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white fs-sm-6 fs-8" aria-current="page" href="{{ url('/') }}"><i class="bi bi-envelope"></i> talktous@freshful.in</a>
            </li>
        </ul>
        <!-- <a class="navbar-brand" href="{{ url('/') }}">Navbar</a> -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-sm-end top-menu" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 gap-sm-4">
                <li class="nav-item">
                    <a class="nav-link active text-white" aria-current="page" href="{{ url('/about') }}">About us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active text-white" aria-current="page" href="{{ url('/franchisee') }}">Franchise</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active text-white" aria-current="page" href="{{ route('certificate') }}">Certificate</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active text-white" aria-current="page" href="{{ url('/refer_earn') }}">Refer & Earn</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active text-white" aria-current="page" href="{{ url('/contact_us') }}">Contact Us</a>
                </li>
            </ul>

        </div>
    </div>
</nav>

<header id="myHeader" class="header bg-white border-bottom py-2 position-relative">
    <div class="container-fluid container-xl">
        <div class="row align-items-center justify-content-between g-2 g-sm-3 g-md-3 g-lg-3">
            <div class="col-sm-6 col-6 col-md-2 col-lg-2 rder-lg-1 order-1">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('assets/image/logo.png') }}" alt="Logo" class="logoImg img-fluid">

                </a>
            </div>
            <div class="  col-12 col-sm-6 col-md-3 col-lg-2  order-lg-2 order-last">
                <div id="open-location" class="d-flex align-items-center justify-content-start gap-2"
                    data-bs-toggle="collapse" data-bs-target="#location-dropdown" style="cursor:pointer;">
                    <i class="bi bi-geo-alt fs-3 text-primary-alt"></i>
                    <div class="d-flex align-items-center flex-row flex-md-column justify-content-start gap-2 gap-md-0">
                        <p class="fs-7 fw-bolder m-0 text-capitalize">amutart tamil</p>
                        <div class="d-flex align-items-start justify-content-start gap-2">
                            <p class="fs-8  m-0 text-capitalize">amutart tamil</p>
                            <i class="bi bi-chevron-down fs-8"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-6 col-md-3 col-lg-4 d-none d-sm-block rder-lg-3 order-3">

                <div class="position-relative">
                    <div class="input-group rounded-pill overflow-hidden border py-1 mx-auto">
                        <span class="input-group-text bg-white border-0">
                            <i class="bi bi-search text-primary-alt"></i>
                        </span>
                        <input
                            type="search"
                            id="search_product"
                            class="form-control border-0 outline-0 bg-white"
                            placeholder="Search products..."
                            aria-label="Search"
                            autocomplete="off">
                    </div>
                    <div id="search-results-dropdown"
                         style="display:none;position:absolute;top:100%;left:0;right:0;z-index:9999;background:#fff;border:1px solid #ddd;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);max-height:360px;overflow-y:auto;margin-top:4px;">
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-6 col-md-3 col-lg-3 rder-lg-4 order-4">
                <div class="d-flex gap-3 align-items-center justify-content-lg-center justify-content-end">
                    @if(Auth::check())
                    <a href="{{ route('myaccount') }}" class="border rounded-pill px-md-4 py-md-2 d-flex align-items-center gap-2 header-btn">My Account</a>
                    @else
                    <a data-bs-toggle="offcanvas" href="#loginoffcanvas" role="button" aria-controls="loginoffcanvas" class="border rounded-pill px-md-4 py-md-2 d-flex align-items-center gap-2 header-btn top-login-btn">
                        <i class="bi bi-person-circle"></i>
                        <span class="d-none d-md-inline">Login</span>
                    </a>
                    @endif
                    <a data-bs-toggle="offcanvas" href="#cardoffcanvas" role="button" aria-controls="cardoffcanvas" class="border rounded-pill px-md-4 py-md-2 d-flex align-items-center gap-2 header-btn position-relative">
                        <i class="bi bi-cart"></i>
                        <span class="d-none d-md-inline">Cart</span>
                        <span id="cart-badge-count" style="display:none;position:absolute;top:6px;right:6px;background:#e53935;color:#fff;border-radius:50%;width:20px;height:20px;font-size:11px;align-items:center;justify-content:center;font-weight:700;"></span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="location-dropdown collapse" id="location-dropdown">
        <card class=" py-3 py-sm-5 px-sm-5 px-0">
            <h6 class="fw-bold fs-5 mb-4 text-center text-capitalize">Choose delivery location</h6>
            <div class='w-100  mx-auto px-sm-5 px-0'>
                <div class="input-group rounded-0 overflow-hidden border py-1 mx-auto">
                    <span class="input-group-text bg-white border-0">
                        <i class="bi bi-search text-primary-alt"></i>
                    </span>
                    <input
                        type="text"
                        id="locationSearchInput"
                        class="form-control border-0 outline-0 bg-white"
                        placeholder="Enter address here..."
                        autocomplete="off">
                </div>
                <div id="location-status" class="mt-2 small text-center" style="min-height:20px;"></div>
                <div class="d-flex align-items-start justify-content-center gap-2 mt-3">
                    <i class="bi bi-crosshair fs-7" style="color:#0066ee"></i>
                    <p class="fs-7 fw-bold m-0 text-capitalize" style="color:#0066ee;cursor:pointer;" onclick="useMyLocation()">Use my current location</p>
                </div>
            </div>
        </card>
    </div>
</header>



<!-- Login Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="loginoffcanvas" aria-labelledby="loginoffcanvasLabel"  style="background-image: url('{{ asset('assets/image/other/bd_image.jpg') }}')">
    <div class="offcanvas-header">
        <div class="offcanvas-title" id="loginoffcanvasLabel">
            <img src="{{ asset('assets/image/logo.png') }}" alt="Logo" class="logoImg img-fluid">
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body card mx-3 h-auto ">
        <div>
            <h5 class="fs-5 mt-1 fw-bolder text-center">Sign In/Sign Up</h5>
        </div>
        <ul class="nav nav-pills my-4 mx-auto align-items-center" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link  active fs-6" id="pills-otp-tab" data-bs-toggle="pill"
                    data-bs-target="#pills-otp" type="button" role="tab" aria-controls="pills-otp"
                    aria-selected="true" style="border-radius: 5px;">
                    OTP Login
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link  fs-6" id="pills-password-tab" data-bs-toggle="pill"
                    data-bs-target="#pills-password" type="button" role="tab" aria-controls="pills-password"
                    aria-selected="false" style="border-radius: 5px;">
                    Password Login
                </button>
            </li>

        </ul>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if(session()->has('success'))
            <div class="alert alert-success">
                {{ session()->get('success') }}
            </div>
        @endif

        <!-- Pills content -->
        <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="pills-otp" role="tabpanel"
                aria-labelledby="pills-otp-tab">

                <form action="{{ route('user.login') }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <label for="phone" class="form-label fs-7 fw-bolder mb-1 text-black-50 text-start">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-telephone"></i>
                            </span>
                            <input
                                type="tel"
                                class="form-control"
                                id="phoneLogin"
                                name="phone"
                                pattern="[0-9]{10}"
                                value="{{ old('phone') }}"

                                required>
                            <div class="invalid-feedback">
                                Please enter a valid 10-digit phone number.
                            </div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="phone" class="form-label fs-7 fw-bolder mb-1 text-black-50 text-start">OTP</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input
                                type="tel"
                                class="form-control"
                                name="otp"
                                pattern="[0-9]{6}">
                            <div class="invalid-feedback">
                                Please enter a valid 6-digit OTP.
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary fs-7 fw-normal py-1" id='sendOtpBtn' style="border-radius: 5px;">Send OTP</button>
                    </div>
                    <div class="d-flex gap-2 w-100 text-center">
                        <button type="submit" class="btn btn-primary rounded-pill w-100 flex-1">Login with OTP</button>
                    </div>
                </form>


            </div>
            <div class="tab-pane fade" id="pills-password" role="tabpanel"
                aria-labelledby="pills-password-tab">
                <form action="{{ route('user.login') }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <label for="phone" class="form-label fs-7 fw-bolder mb-1 text-black-50 text-start">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-telephone"></i>
                            </span>
                            <input
                                type="tel"
                                class="form-control"
                                id=""
                                name="phone"
                                pattern="[0-9]{10}"
                                value="{{ old('phone') }}"

                                required>
                            <div class="invalid-feedback">
                                Please enter a valid 10-digit phone number.
                            </div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="phone" class="form-label fs-7 fw-bolder mb-1 text-black-50 text-start">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-shield-lock"></i>
                            </span>
                            <input
                                type="password"
                                class="form-control"
                                id=""
                                name="password"

                                required>
                            <div class="invalid-feedback">
                                Please enter a valid Password.
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <a href="{{ url('/') }}" class="text-primary-alt fs-7 fw-normal py-1" style="border-radius: 5px;">forgot password?</a>
                    </div>
                    <div class="d-flex gap-2 w-100 text-center">
                        <button type="submit" class="btn btn-primary rounded-pill w-100 flex-1">Login with Password</button>
                    </div>
                </form>
            </div>

        </div>

        <div class="mt-3 text-center">
            <p class="fs-7 mb-0">By signing in you agree to our</p>
            <a href="{{ url('/terms') }}" target="_blank" class="text-primary-alt fs-6 fw-bold">Terms & Conditions</a>
        </div>
    </div>
</div>

<!-- Order Summary Offcanvas -->

<div class="offcanvas offcanvas-end" tabindex="-1" id="cardoffcanvas" aria-labelledby="cardoffcanvasLabel bg-white">
    <div class="offcanvas-header mb-1">
        <div class="offcanvas-title" id="cardoffcanvasLabel">
            <h6 class="text-center fs-5 fw-bolder">Order Summary</h6>
        </div>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body  mx-1 h-auto ">
        <div class="card order-card shadow-sm">
            <!-- Item -->
            <div class="card-body ">

                <div id="cart-items"></div>



                <!-- Bill Details -->
                <h6 class="fw-bold">BILL DETAILS</h6>

                <div class="d-flex justify-content-between mt-1 pb-1 border-bottom ">
                    <span class="fs-7">Subtotal</span>
                    <span class="fs-7"  id="subtotal">₹0</span>
                </div>
                <div class="d-flex justify-content-between mt-1 pb-1 border-bottom">
                    <span class="fs-7">Delivery Charge</span>
                    <span class="fs-7" id="delivery">₹0</span>
                </div>
                <div class="d-flex justify-content-between mt-1 pb-1 border-bottom">
                    <span class="fs-7">Discount</span>
                    <span class="fs-7" id="discount">₹0</span>
                </div>



                <div class="d-flex justify-content-between fw-bold mt-2">
                    <span>Total</span>
                    <span class="text-primary fs-6" id="total">₹0</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-3 total-bar d-flex card-footer shadow-sm justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold fs-5">Total : <span class="text-primary fs-4" id="footer-total">₹0</span></h5>
                @auth
                <a href="{{ url('/checkout') }}" class="btn btn-primary py-2 fw-bold">
                    Proceed To Checkout
                </a>
                @else
                <button type="button" class="btn btn-primary py-2 fw-bold" onclick="openLoginFromCart()">
                    Login to Checkout
                </button>
                @endauth
            </div>
        </div>
    </div>
</div>