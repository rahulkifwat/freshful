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
                                @php
                                    $pending_statuses  = ['Order Pending','Order Placed','Order Processed','Order Shipped','Order Delivered','Order Dispatched'];
                                    $ongoing_statuses  = ['Order Placed','Order Processed','Order Shipped','Order Dispatched'];
                                    $delivered_statuses= ['Order Delivered'];
                                    $status_colors = [
                                        'Order Pending'    => 'background:#858585;color:#fff',
                                        'Order Cancel'     => 'background:#e52121;color:#fff',
                                        'Order Placed'     => 'background:#e0389b;color:#fff',
                                        'Order Processed'  => 'background:#fa8d12;color:#fff',
                                        'Order Shipped'    => 'background:#099cc0;color:#fff',
                                        'Order Delivered'  => 'background:#17cf1c;color:#fff',
                                        'Order Dispatched' => 'background:#f3db1b;color:#000',
                                    ];
                                @endphp

                                @forelse($orders as $order)
                                <div class="border rounded mb-3 bg-white overflow-hidden">
                                    <div class="p-3">
                                        <div class="row align-items-start">
                                            <div class="col-md-8">
                                                <p class="mb-1 fw-semibold">Order ID: <span class="text-danger">{{ $order->order_id }}</span></p>
                                                <p class="mb-1 small text-muted">Placed: {{ date('d M Y (h:i A)', strtotime($order->date_added)) }} &bull; {{ $order->order_count }} item(s)</p>
                                                <p class="mb-1 small text-muted">
                                                    Delivery:
                                                    @if($order->delivery_type === 'Express')
                                                        <strong>Express</strong>
                                                    @else
                                                        <strong>{{ $order->schedule_time }}</strong>
                                                    @endif
                                                </p>
                                                <p class="mb-0">
                                                    STATUS:
                                                    <span class="px-2 py-1 rounded small fw-semibold"
                                                          style="{{ $status_colors[$order->order_status] ?? 'background:#858585;color:#fff' }}">
                                                        {{ $order->order_status }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="col-md-4 mt-3 mt-md-0">
                                                <ul class="list-unstyled mb-0 small" style="border-left:3px solid #eee;padding-left:12px;">
                                                    <li class="mb-2 {{ in_array($order->order_status, $pending_statuses) ? 'text-danger fw-bold' : 'text-muted' }}">
                                                        <span class="me-1">●</span> Order Pending
                                                    </li>
                                                    <li class="mb-2 {{ in_array($order->order_status, $ongoing_statuses) ? 'text-warning fw-bold' : 'text-muted' }}">
                                                        <span class="me-1">●</span> Order Ongoing
                                                    </li>
                                                    <li class="{{ in_array($order->order_status, $delivered_statuses) ? 'text-success fw-bold' : 'text-muted' }}">
                                                        <span class="me-1">●</span> Completed
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="border-top d-flex">
                                        <a href="{{ route('track-order', $order->order_id) }}"
                                           class="btn btn-primary flex-grow-1 rounded-0">View Details</a>
                                        @if($order->order_status !== 'Order Cancel')
                                        <a href="{{ route('cancel-order', $order->order_id) }}"
                                           class="btn btn-danger rounded-0">Cancel Order</a>
                                        @endif
                                    </div>
                                </div>
                                @empty
                                <p class="text-muted text-center py-4">No orders yet.</p>
                                @endforelse
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
