@extends('layout.master')
@section('content')
    <main>
        <section class="section myaccount-section py-5">
            <div class="container">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center">
                            <img src="https://shreethemes.in/orderit/layouts/assets/images/client/1.jpg"
                                class="avatar avatar-md-md rounded-circle" alt="">
                            <div class="ms-3">
                                <h6 class="text-muted mb-0">Hello,</h6>
                                <h5 class="mb-0">{{ $buyer->name }}</h5>
                            </div>
                        </div>
                    </div><!--end col-->

                    <div class="col-md-8 mt-4 mt-sm-0 pt-2 pt-sm-0">
                        <p class="text-muted mb-0">We offer flexible delivery options tailored to your needs—whether it's
                            urgent same-day supplies, or specialty products.</p>
                    </div><!--end col-->
                </div><!--end row-->

                <div class="row">
                    <div class="col-md-4 mt-4 pt-2">
                        <ul class="nav nav-pills nav-justified flex-column rounded-6 shadow p-3 mb-0" id="pills-tab"
                            role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link rounded-6 p-" id="dashboard" data-bs-toggle="pill" href="#dash"
                                    role="tab" aria-controls="dash" aria-selected="false" tabindex="-1">
                                    <div class="text-start">
                                        <h6 class="mb-0"><i class="ri-dashboard-line h5 align-middle me-2 mb-0"></i>
                                            Dashboard</h6>
                                    </div>
                                </a><!--end nav link-->
                            </li><!--end nav item-->

                            <li class="nav-item mt-2" role="presentation">
                                <a class="nav-link rounded-6 p- active" id="order-history" data-bs-toggle="pill"
                                    href="#orders" role="tab" aria-controls="orders" aria-selected="true">
                                    <div class="text-start">
                                        <h6 class="mb-0"><i class="ri-list-check h5 align-middle me-2 mb-0"></i> Orders
                                        </h6>
                                    </div>
                                </a><!--end nav link-->
                            </li><!--end nav item-->

                            <li class="nav-item mt-2" role="presentation">
                                <a class="nav-link rounded-6 p-" id="download" data-bs-toggle="pill" href="#down"
                                    role="tab" aria-controls="down" aria-selected="false" tabindex="-1">
                                    <div class="text-start">
                                        <h6 class="mb-0"><i class="ri-download-line h5 align-middle me-2 mb-0"></i>
                                            Downloads</h6>
                                    </div>
                                </a><!--end nav link-->
                            </li><!--end nav item-->

                            <li class="nav-item mt-2" role="presentation">
                                <a class="nav-link rounded-6 p-" id="addresses" data-bs-toggle="pill" href="#address"
                                    role="tab" aria-controls="address" aria-selected="false" tabindex="-1">
                                    <div class="text-start">
                                        <h6 class="mb-0"><i class="ri-road-map-line h5 align-middle me-2 mb-0"></i>
                                            Addresses</h6>
                                    </div>
                                </a><!--end nav link-->
                            </li><!--end nav item-->

                            <li class="nav-item mt-2" role="presentation">
                                <a class="nav-link rounded-6 p-" id="account-details" data-bs-toggle="pill" href="#account"
                                    role="tab" aria-controls="account" aria-selected="false" tabindex="-1">
                                    <div class="text-start">
                                        <h6 class="mb-0"><i class="ri-user-line h5 align-middle me-2 mb-0"></i> Account
                                            Details</h6>
                                    </div>
                                </a><!--end nav link-->
                            </li><!--end nav item-->

                            <li class="nav-item mt-2" role="presentation">
                                <a class="nav-link rounded-6 p-" role="tab" href="{{ route('user.logout') }}"
                                    aria-selected="false" tabindex="-1">
                                    <div class="text-start">
                                        <h6 class="mb-0"><i class="ri-logout-circle-r-line h5 align-middle me-2 mb-0"></i>
                                            Logout</h6>
                                    </div>
                                </a><!--end nav link-->
                            </li><!--end nav item-->
                        </ul><!--end nav pills-->
                    </div><!--end col-->

                    <div class="col-md-8 col-12 mt-4 pt-2">
                        <div class="tab-content" id="pills-tabContent">
                            <div class="tab-pane fade shadow-sm rounded-6 p-4" id="dash" role="tabpanel"
                                aria-labelledby="dashboard">
                                <h6 class="text-muted fw-normal">Hello <span class="text-dark">calvin_carlo</span> (not
                                    <span class="text-dark">calvin_carlo</span>? <a href="javascript:void(0)"
                                        class="text-danger">Log out</a>)</h6>

                                <h6 class="text-muted fw-normal mb-0">From your account dashboard you can view your <a
                                        href="javascript:void(0)" class="text-danger">recent orders</a>, manage your <a
                                        href="javascript:void(0)" class="text-danger">shipping and billing addresses</a>,
                                    and <a href="javascript:void(0)" class="text-danger">edit your password and account
                                        details</a>.</h6>
                            </div><!--end teb pane-->

                            <div class="tab-pane fade shadow rounded-6 p-4 active show" id="orders" role="tabpanel"
                                aria-labelledby="order-history">
                                <div class="table-responsive bg-white   rounded-6">
                                    <table class="table mb-0 table-center table-nowrap">
                                        <thead>
                                            <tr>
                                                <th scope="col" class="border-bottom fw-medium">Order no.</th>
                                                <th scope="col" class="border-bottom fw-medium">Date</th>
                                                <th scope="col" class="border-bottom fw-medium">Status</th>
                                                <th scope="col" class="border-bottom fw-medium">Total</th>
                                                <th scope="col" class="border-bottom fw-medium">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($orders as $order)
                                                <tr>
                                                    <th>{{ $order->order_id }}</th>
                                                    <td>{{ date('d M Y', strtotime($order->date_added)) }}</td>

                                                    <td>
                                                        <span
                                                            class="
                                                    {{ $order->status == 'delivered' ? 'text-success' : '' }}
                                                    {{ $order->status == 'pending' ? 'text-warning' : '' }}
                                                    {{ $order->status == 'cancelled' ? 'text-danger' : '' }}
                                                ">
                                                            {{ ucfirst($order->order_status) }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        ₹ {{ $order->total_amount }}
                                                        <span class="text-muted">for {{ $order->order_count }} items</span>
                                                    </td>

                                                    <td>
                                                        <a href="{{ url('/order/' . $order->order_id) }}"
                                                            class="text-primary">
                                                            View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div><!--end teb pane-->

                            <div class="tab-pane fade shadow rounded-6 p-4" id="down" role="tabpanel"
                                aria-labelledby="download">
                                <div class="table-responsive bg-white   ">
                                    <table class="table mb-0 table-center table-nowrap">
                                        <thead>
                                            <tr>
                                                <th scope="col" class="border-bottom fw-medium">Product Name</th>
                                                <th scope="col" class="border-bottom fw-medium">Description</th>
                                                <th scope="col" class="border-bottom fw-medium">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th scope="row" class="fw-normal">Quick heal</th>
                                                <td class="text-muted fw-normal">It is said that song composers of the past
                                                    <br> used dummy texts as lyrics when writing <br> melodies in order to
                                                    have a 'ready-made' <br> text to sing with the melody.</td>
                                                <td class="text-success fw-normal">Downloaded</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div><!--end teb pane-->

                            <div class="tab-pane fade shadow rounded-6 p-4" id="address" role="tabpanel"
                                aria-labelledby="addresses">
                                <h6 class="text-muted fw-normal mb-0">The following addresses will be used on the checkout
                                    page by default.</h6>

                                <div class="row">
                                    @foreach ($addresses as $address)
                                        <div class="col-lg-6 mt-4 pt-2">
                                            <div class="d-flex justify-content-between">
                                                <h5>{{ ucfirst($address->type) }} Address</h5>
                                                <a href="{{ route('deleteAddress', $address->id) }}"
                                                    class="text-danger">Delete</a>
                                            </div>

                                            <p>{{ $address->street_name }}</p>
                                            <p>{{ $address->landmark }}</p>
                                            <p>{{ $address->city }} - {{ $address->pincode }}</p>
                                            <p>{{ $address->phone }}</p>
                                        </div>
                                    @endforeach
                                </div>
                                <div class='row'>
                                    <button class="btn btn-danger" data-bs-toggle="modal"
                                        data-bs-target="#addAddressModal">
                                        Add New Address
                                    </button>
                                </div>
                            </div><!--end teb pane-->

                            <div class="tab-pane fade shadow rounded-6 p-4" id="account" role="tabpanel"
                                aria-labelledby="account-details">
                                <form action="{{ route('updateAccount') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-normal">Name</label>
                                                <div class="form-icon position-relative">
                                                    <i
                                                        class="ri-user-line position-absolute top-50 start-0 translate-middle-y ms-3 fs-6"></i>
                                                    <input name="name" id="first-name" type="text"
                                                        class="form-control ps-5" value="{{ Auth::user()->name }}">
                                                </div>
                                            </div>
                                        </div><!--end col-->
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-normal">Gender</label>
                                                <div class="form-icon position-relative">
                                                    <i
                                                        class="ri-user-follow-line icons position-absolute top-50 start-0 translate-middle-y ms-3 fs-6"></i>
                                                        <select name="gender" class="form-select ps-5">
                                                            <option {{ Auth::user()->gender = 'Male' ?? 'selected' }} value="Male">Male</option>
                                                            <option {{ Auth::user()->gender = 'Female' ?? 'selected' }} value="Female">Female</option>
                                                        </select>
                                                </div>
                                            </div>
                                        </div><!--end col-->
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-normal">Your Email</label>
                                                <div class="form-icon position-relative">
                                                    <i
                                                        class="ri-mail-line icons position-absolute top-50 start-0 translate-middle-y ms-3 fs-6"></i>
                                                    <input name="email" id="email" type="email"
                                                        class="form-control ps-5" value="{{ Auth::user()->email }}">
                                                </div>
                                            </div>
                                        </div><!--end col-->
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-normal">Phone Number</label>
                                                <div class="form-icon position-relative">
                                                    <i
                                                        class="ri-shield-user-line icons position-absolute top-50 start-0 translate-middle-y ms-3 fs-6"></i>
                                                    <input name="phone" id="display-name" type="text"
                                                        class="form-control ps-5" value="{{ Auth::user()->phone }}" readonly>
                                                </div>
                                            </div>
                                        </div><!--end col-->

                                        <div class="col-lg-12 mt-2 mb-0">
                                            <button class="btn btn-primary">Save Changes</button>
                                        </div><!--end col-->
                                    </div><!--end row-->
                                </form>

                                <h5 class="mt-4">Change password :</h5>
                                <form>
                                    <div class="row mt-3">
                                        <div class="col-lg-12">
                                            <div class="mb-3">
                                                <label class="form-label fw-normal">Old password :</label>
                                                <div class="form-icon position-relative">
                                                    <i
                                                        class="ri-key-line icons position-absolute top-50 start-0 translate-middle-y ms-3 fs-6"></i>
                                                    <input type="password" class="form-control ps-5"
                                                        placeholder="Old password" required="">
                                                </div>
                                            </div>
                                        </div><!--end col-->

                                        <div class="col-lg-12">
                                            <div class="mb-3">
                                                <label class="form-label fw-normal">New password :</label>
                                                <div class="form-icon position-relative">
                                                    <i
                                                        class="ri-key-line icons position-absolute top-50 start-0 translate-middle-y ms-3 fs-6"></i>
                                                    <input type="password" class="form-control ps-5"
                                                        placeholder="New password" required="">
                                                </div>
                                            </div>
                                        </div><!--end col-->

                                        <div class="col-lg-12">
                                            <div class="mb-3">
                                                <label class="form-label fw-normal">Re-type New password :</label>
                                                <div class="form-icon position-relative">
                                                    <i
                                                        class="ri-key-line icons position-absolute top-50 start-0 translate-middle-y ms-3 fs-6"></i>
                                                    <input type="password" class="form-control ps-5"
                                                        placeholder="Re-type New password" required="">
                                                </div>
                                            </div>
                                        </div><!--end col-->

                                        <div class="col-lg-12 mt-2 mb-0">
                                            <button class="btn btn-primary">Save Password</button>
                                        </div><!--end col-->
                                    </div><!--end row-->
                                </form>
                            </div><!--end teb pane-->
                        </div>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!--end container-->
        </section>
        <div class="modal fade" id="addAddressModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content p-4 rounded-4">

                    <div class="modal-header border-0">
                        <h5 class="modal-title">Add New Address</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form action="{{ route('storeAddress') }}" method="POST">
                        @csrf

                        <div class="modal-body">
                            <div class="row">
                                <div class="col-6">Map</div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <input type="text" name="locality" class="form-control border-0 border-bottom"
                                            placeholder="locality" value="{{ request()->cookie('locality') }}" required>
                                    </div>

                                    <div class="mb-3">
                                        <input type="text" name="name" class="form-control border-0 border-bottom"
                                            placeholder="Full Name" required>
                                    </div>

                                    <div class="mb-3">
                                        <input type="text" name="phone" class="form-control border-0 border-bottom"
                                            placeholder="Mobile" required>
                                    </div>

                                    <div class="mb-3">
                                        <input type="text" name="street_name" class="form-control border-0 border-bottom"
                                            placeholder="Flat no./Building/Street" required>
                                    </div>

                                    <div class="mb-3">
                                        <input type="text" name="landmark" class="form-control border-0 border-bottom"
                                            placeholder="Landmark">
                                    </div>

                                    <div class="mb-3">
                                        <input type="text" name="city" class="form-control border-0 border-bottom"
                                            placeholder="City" required>
                                    </div>

                                    <div class="mb-3">
                                        <select name="type" class="form-control border-0 border-bottom">
                                            <option value="home">Home</option>
                                            <option value="work">Work</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-danger px-5">Save</button>
                                    </div>

                                </div>
                            </div>

                        </div>

                        {{-- <div class="modal-footer border-0 justify-content-center">
                            
                        </div> --}}

                    </form>

                </div>
            </div>
        </div>
    </main>
@endsection
