@extends('layout.master')
@section('content')

<style>
body:has(.ff-checkout) { background:#f5f6f5; }
.ff-checkout { padding:1.4rem 0 3rem; }
.ff-address-modal { overflow:hidden; border:0; border-radius:20px; box-shadow:0 24px 70px rgba(20,30,25,.2); }
.ff-address-modal .modal-header,.ff-address-modal .modal-footer { padding:1rem 1.25rem; border-color:#edf0ed; }
.freshful-address-map, .ff-address-map { display:block; width:100%; height:330px; min-height:300px; overflow:hidden; border:1px solid #e7ebe6; border-radius:14px; background:#f2f5f0; }
@media(max-width:767px) { .freshful-address-map, .ff-address-map { height:250px; min-height:250px; } .ff-address-modal .modal-body { max-height:75vh; overflow:auto; } }

.ff-checkout .container { max-width:1240px; }
.ff-checkout .row { align-items:start; }
.ff-checkout .bg-white,.ff-checkout .card { border:1px solid #e9ede9!important; border-radius:18px!important; box-shadow:0 8px 25px rgba(24,39,29,.055)!important; }
.ff-checkout h5,.ff-checkout h6 { color:#202923; }
.ff-checkout .btn-danger { border-radius:999px; padding:.72rem 1.2rem; font-weight:800; }
.ff-checkout .btn-outline-secondary { border-radius:999px; }
.ff-checkout .card.border-danger { border:1.5px solid #f43b26!important; background:#fffdfb; }
.ff-checkout .form-control { min-height:44px; border-color:#e4e9e4; border-radius:10px; }
.ff-checkout .form-control:focus { border-color:#71b784; box-shadow:0 0 0 .2rem rgba(36,139,73,.12); }
.ff-checkout .step-icon { width:18px; height:18px; box-shadow:0 0 0 4px #ffefec; }
.ff-checkout .step-icon.done { box-shadow:0 0 0 4px #e9f6ec; }
.ff-checkout .payment-options-list { border-color:#edf0ed; }
.ff-checkout .payment-options-list label { border-radius:10px; }
.ff-checkout .ff-checkout-step-label { color:#202923; }
.ff-checkout .ff-promo-modal { border:0; border-radius:22px; box-shadow:0 24px 70px rgba(20,30,25,.2); }
.ff-checkout .ff-promo-input { padding-bottom:1rem; border-bottom:1px solid #edf0ee; }
.ff-checkout .ff-promo-list { display:grid; gap:.65rem; max-height:min(52vh,480px); overflow:auto; padding:.15rem .1rem; }
.ff-checkout .ff-promo-item { --promo-color:#ff2015; display:grid; grid-template-columns:44px minmax(0,1fr) auto; align-items:center; gap:.7rem; padding:.75rem; border:1px solid #ecefeb; border-left:4px solid var(--promo-color); border-radius:15px; background:#fff; box-shadow:0 4px 14px rgba(24,39,29,.045); }
.ff-checkout .ff-promo-item:nth-child(4n + 2) { --promo-color:#11a451; }
.ff-checkout .ff-promo-item:nth-child(4n + 3) { --promo-color:#2967f5; }
.ff-checkout .ff-promo-item:nth-child(4n + 4) { --promo-color:#7b35ec; }
.ff-checkout .ff-promo-star { display:grid; place-items:center; width:40px; height:40px; clip-path:polygon(50% 0%,61% 13%,78% 6%,83% 24%,100% 30%,92% 48%,100% 64%,82% 73%,81% 92%,62% 88%,50% 100%,39% 87%,21% 94%,17% 75%,0% 68%,8% 50%,0% 34%,18% 25%,19% 7%,38% 13%); background:var(--promo-color); color:#fff; font-size:1.1rem; font-weight:900; }
.ff-checkout .ff-promo-details strong { display:block; color:#202923; line-height:1.2; }
.ff-checkout .ff-promo-details small { display:block; margin-top:.2rem; color:#7a857d; font-size:.76rem; }
.ff-checkout .ff-promo-code { display:inline-block; margin-top:.3rem; padding:.12rem .4rem; border:1px dashed var(--promo-color); border-radius:6px; color:var(--promo-color); font-size:.74rem; font-weight:800; }
.ff-checkout .ff-promo-item .apply-btn { padding:.48rem .85rem; border:0; border-radius:9px; background:var(--promo-color); color:#fff; font-size:.82rem; font-weight:800; }
@media(max-width:767px) { .ff-checkout { padding-top:.75rem; } .ff-checkout .col-md-8,.ff-checkout .col-md-4 { min-width:0; } .ff-checkout .ff-promo-item { grid-template-columns:38px minmax(0,1fr) auto; gap:.55rem; padding:.6rem; } .ff-checkout .ff-promo-star { width:34px; height:34px; } }
[type="radio"]:not(:checked), [type="radio"]:checked { height:20px; width:20px; margin-right:10px; }
.disable_click { opacity:0.4; pointer-events:none; cursor:default; }
.active_time { border:2px solid #e41d36 !important; border-radius:4px; background:#fff0f1; }
.schedule_time { cursor:pointer; }
.schedule_time:hover { border:1px solid #555; border-radius:4px; }
.step-icon { display:inline-block; width:14px; height:14px; border-radius:50%; background:#e41d36; margin-right:8px; vertical-align:middle; }
.step-icon.done { background:green; }
.change-link { color:#e41d36; font-size:13px; cursor:pointer; text-decoration:underline; }
.payment-options-list { border-right:1px solid #eee; min-width:180px; }
.payment-options-list label { display:flex; align-items:center; padding:14px 10px; border-bottom:1px solid #f0f0f0; cursor:pointer; font-weight:500; }
.payment-options-list label:hover { background:#fff5f5; }
</style>

<main class="ff-checkout">
    <div class="container">
        <div class="row g-4">
            <!-- Left: Steps -->
            <div class="col-md-8">

                <!-- Step 1: Address -->
                <div id="saved_address" class="bg-white rounded shadow-sm p-4 mb-3">
                    <h5 class="fw-bold mb-3">Choose a Delivery Address</h5>
                    <div class="row g-3">
                        @forelse($addresses as $address)
                        <div class="col-md-6">
                            <div class="card p-3 h-100 {{ $loop->first ? 'border-danger' : '' }}">
                                <div class="d-flex align-items-start gap-2">
                                    <label class="d-flex align-items-start gap-2 flex-grow-1 mb-0" style="cursor:pointer;">
                                        <input type="radio" name="address_id" value="{{ $address->id }}" {{ $loop->first ? 'checked' : '' }} class="mt-1" aria-label="Deliver to this address">
                                        <span>
                                            <span class="badge bg-danger mb-1">{{ $address->type }}</span>
                                            <span class="d-block mb-0 fw-semibold">{{ $address->name ?? '' }}</span>
                                            <span class="d-block small mb-0 text-muted">{{ $address->street_name }}, {{ $address->locality ?? '' }}, {{ $address->landmark ?? '' }}, {{ $address->city }}</span>
                                            @if($address->phone ?? null)<span class="d-block small mb-0 text-muted">{{ $address->phone }}</span>@endif
                                        </span>
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-success freshful-edit-address"
                                        data-address-id="{{ $address->id }}"
                                        data-address-name="{{ $address->name }}" data-address-phone="{{ $address->phone }}"
                                        data-address-locality="{{ $address->locality ?? '' }}" data-address-street-name="{{ $address->street_name }}"
                                        data-address-landmark="{{ $address->landmark ?? '' }}" data-address-city="{{ $address->city }}"
                                        data-address-type="{{ $address->type }}" data-address-latitude="{{ $address->latitude ?? '' }}"
                                        data-address-longitude="{{ $address->longitude ?? '' }}"
                                        aria-label="Edit address">Edit</button>
                                </div>
                            </div>
                        </div>
                        @empty
                        <p class="text-muted">No saved addresses. Please add one.</p>
                        @endforelse
                        <div class="col-md-6">
                            <div class="card p-3 text-center h-100 d-flex align-items-center justify-content-center"
                                 style="border-style:dashed;cursor:pointer;min-height:80px;"
                                 data-bs-toggle="modal" data-bs-target="#add-address-modal" data-freshful-add-address>
                                <h6 class="mb-0 text-danger">+ ADD NEW ADDRESS</h6>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-danger mt-4 px-4" id="saved_address_btn">DELIVER HERE</button>
                </div>

                <!-- Step 2: Delivery Summary -->
                <div id="delivery_summary" class="bg-white rounded shadow-sm p-4 mb-3" style="display:none;">
                    <h5 class="fw-bold mb-3">Delivery Summary</h5>
                    <ul class="list-unstyled">
                        @foreach($cart_items as $item)
                        <li class="d-flex align-items-center mb-3 border-bottom pb-2">
                            <img src="{{ asset('uploads/images/products/'.($item->product_image ?? '')) }}"
                                 alt="{{ $item->title }}" style="width:50px;height:50px;object-fit:cover;border-radius:6px;margin-right:12px;">
                            <div>
                                <p class="mb-0 fw-semibold">{{ $item->title }}</p>
                                <small class="text-muted">Qty: {{ $item->quantity }} × ₹{{ $item->main_price }}</small>
                            </div>
                            <span class="ms-auto fw-bold">₹{{ $item->quantity * $item->main_price }}</span>
                        </li>
                        @endforeach
                    </ul>
                    <div class="mt-3">
                        @php $ffHourNow = (int) date('G'); $ffExpressOpen = $ffHourNow >= 7 && $ffHourNow < 21; @endphp
                        <h6 class="fw-semibold">Delivery time</h6>
                        <button class="btn btn-outline-danger mt-1" id="time_button">
                            <i class="bi bi-clock me-1"></i><span id="time_button_text">{{ $ffExpressOpen ? 'Express · delivered in 30 min' : 'Select Delivery Slot' }}</span>
                        </button>
                        <input type="hidden" id="schedule_date" value="{{ date('Y-m-d') }}">
                        <input type="hidden" id="schedule_time" value="{{ $ffExpressOpen ? '30 min' : '' }}">
                        <input type="hidden" id="delivery_type" value="{{ $ffExpressOpen ? 'Express' : 'Scheduled' }}">
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-outline-secondary" id="change_address_btn">← Change Address</button>
                        <button class="btn btn-danger px-4" id="delivery_summary_btn">PROCEED TO PAYMENT</button>
                    </div>
                </div>

                <!-- Step 3: Payment -->
                <div id="payment_page" class="bg-white rounded shadow-sm p-4 mb-3" style="display:none;">
                    <h5 class="fw-bold mb-3">Payment Method</h5>
                    <div class="d-flex">
                        <div class="payment-options-list me-3">
                            <label>
                                <input type="radio" name="payment_type" value="Cash" checked> COD
                            </label>
                            <label>
                                <input type="radio" name="payment_type" value="Online"> Online/UPI/Banking
                            </label>
                            <label>
                                <input type="radio" name="payment_type" value="Wallet"
                                    {{ ($cart_total + $delivery_charge) > ($buyer->wallet_amount ?? 0) ? 'disabled' : '' }}>
                                Wallet (₹{{ $buyer->wallet_amount ?? 0 }})
                            </label>
                        </div>
                        <div class="flex-grow-1 d-flex align-items-center">
                            <div id="payment_cod_info" class="text-muted small">Pay with cash at your doorstep.</div>
                            <div id="payment_online_info" class="text-muted small" style="display:none;">Secure payment via UPI, Net Banking, or Cards via Razorpay.</div>
                            <div id="payment_wallet_info" class="text-muted small" style="display:none;">Pay using your Freshful wallet balance.</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-outline-secondary" id="change_delivery_btn">← Change Delivery Slot</button>
                        <button class="btn btn-danger px-4 fw-bold" id="place_order"
                            data-promo="no" data-code=""
                            data-total="{{ $cart_total + $delivery_charge }}">
                            Place Order
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right: Nav + Bill -->
            <div class="col-md-4">
                <!-- Step nav -->
                <div class="card shadow-sm mb-3 p-3">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3 d-flex align-items-center justify-content-between">
                            <div><span class="step-icon" id="change_address_icon"></span><strong>Choose Address</strong></div>
                            <span class="change-link d-none" id="change_address_link" onclick="$('#delivery_summary').hide();$('#payment_page').hide();$('#saved_address').show();resetNav();">Change</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center justify-content-between">
                            <div><span class="step-icon" id="change_delivery_summary_icon"></span><strong>Delivery Summary</strong></div>
                            <span class="change-link d-none" id="change_delivery_link" onclick="$('#payment_page').hide();$('#delivery_summary').show();$('#change_delivery_summary_icon').removeClass('done');">Change</span>
                        </li>
                        <li class="d-flex align-items-center">
                            <span class="step-icon" id="payment_method_icon"></span><strong>Payment Method</strong>
                        </li>
                    </ul>
                </div>

                <!-- Bill Details -->
                <div class="card shadow-sm p-3 sticky-top" style="top:80px;">
                    <h6 class="fw-bold border-bottom pb-2">BILL DETAILS</h6>
                    <button class="btn btn-outline-secondary btn-sm w-100 mb-3" id="apply_promo_btn" data-bs-toggle="modal" data-bs-target="#promocode"><i class="bi bi-tag me-1"></i>Find promo code</button>
                    <div class="d-flex justify-content-between mb-1"><span>Subtotal</span><span>₹{{ $cart_total }}</span></div>
                    <div class="d-flex justify-content-between mb-1"><span>Delivery Charge</span><span>₹{{ $delivery_charge }}</span></div>
                    <div class="d-flex justify-content-between mb-1 text-success"><span>Discount</span><span class="discounttt">₹0</span></div>
                    <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2">
                        <span>Total</span>
                        <span class="grand_total" id="sub_total" data-amount="{{ $cart_total + $delivery_charge }}">₹{{ $cart_total + $delivery_charge }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Delivery Slot Modal -->
<div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="myModalLabel">When should we deliver?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <style>
                    #myModal .ff-express{display:flex;align-items:center;gap:14px;border:2px solid #f53b13;border-radius:14px;padding:14px 16px;cursor:pointer;background:#fff6f2;margin-bottom:10px}
                    #myModal .ff-express.active_time{box-shadow:0 0 0 3px rgba(245,59,19,.22)}
                    #myModal .ff-express.disable_click{border-color:#e5e7eb;background:#f8f9fa;cursor:not-allowed;opacity:.75}
                    #myModal .ff-x-ic{width:46px;height:46px;border-radius:12px;background:#f53b13;color:#fff;display:grid;place-items:center;font-size:22px;flex:none}
                    #myModal .ff-express.disable_click .ff-x-ic{background:#9ca3af}
                    #myModal .ff-x-tag{margin-left:auto;font-weight:800;color:#f53b13;font-size:18px;white-space:nowrap}
                    #myModal .ff-or{text-align:center;color:#6b7280;font-size:13px;margin:14px 0 12px}
                    #myModal .slot-time{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;padding:0}
                    #myModal .slot-time .schedule_time{margin:0!important;border-radius:12px;padding:12px 10px!important;font-weight:600;background:#fff;text-align:center}
                    #myModal .slot-time .schedule_time.active_time{border-color:#f53b13!important;background:#fff6f2;color:#c2410c}
                    #myModal .slot-time .schedule_time.disable_click{display:none!important}
                </style>
                <div class="ff-express schedule_time {{ $ffExpressOpen ? '' : 'disable_click' }}" id="time_express" data-type="Express" data-time="30 min" data-label="Express · delivered in 30 min" role="button" tabindex="0">
                    <div class="ff-x-ic"><i class="bi bi-lightning-charge-fill"></i></div>
                    <div><div class="fw-bold">Express delivery</div><div class="small text-muted">{{ $ffExpressOpen ? 'Fresh to your door in about 30 minutes' : 'Available every day from 7 AM to 9 PM' }}</div></div>
                    <span class="ff-x-tag">30 min</span>
                </div>
                <div class="ff-or">or schedule for a time that suits you</div>
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-danger day_time active_time" data-day_type="today">Today ({{ date('d F') }})</button>
                    <button class="btn btn-outline-danger day_time" data-day_type="tomorrow">Tomorrow ({{ date('d F', strtotime('+1 day')) }})</button>
                </div>
                <div class="slot-day text-center fw-bold mb-3" id="slot_day_label">Today ({{ date('d F') }})</div>
                <ul class="list-inline slot-time text-center">
                    @php $current_hour = (int) date('G'); @endphp
                    @php
                        $slots = [
                            ['id'=>'time_7',  'label'=>'7 AM – 9 AM',   'hour'=>7],
                            ['id'=>'time_9',  'label'=>'9 AM – 11 AM',  'hour'=>9],
                            ['id'=>'time_11', 'label'=>'11 AM – 1 PM',  'hour'=>11],
                            ['id'=>'time_13', 'label'=>'1 PM – 3 PM',   'hour'=>13],
                            ['id'=>'time_15', 'label'=>'3 PM – 5 PM',   'hour'=>15],
                            ['id'=>'time_17', 'label'=>'5 PM – 7 PM',   'hour'=>17],
                            ['id'=>'time_19', 'label'=>'7 PM – 9 PM',   'hour'=>19],
                            ['id'=>'time_21', 'label'=>'9 PM – 11 PM',  'hour'=>21],
                        ];
                    @endphp
                    @foreach($slots as $slot)
                    <li class="list-inline-item p-2 border mb-2 schedule_time {{ $current_hour >= $slot['hour'] ? 'disable_click' : '' }}"
                        id="{{ $slot['id'] }}" data-type="Scheduled" data-time="{{ $slot['label'] }}">
                        {{ $slot['label'] }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Add or edit delivery address -->
<div class="modal fade" id="add-address-modal" tabindex="-1" aria-labelledby="addAddressLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content ff-address-modal">
            <div class="modal-header">
                <div><h5 class="modal-title fw-bold" id="addAddressLabel">Add delivery address</h5><p class="small text-muted mb-0">Search or pin your location, then complete the address details.</p></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('storeAddress') }}" method="POST" data-freshful-address-form>
                @csrf
                <input type="hidden" name="address_id" value="">
                <input type="hidden" name="latitude" value="">
                <input type="hidden" name="longitude" value="">
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <label class="form-label fw-semibold" for="freshful-address-map-search">Find your location</label>
                            <div class="input-group mb-2"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input id="freshful-address-map-search" class="form-control" placeholder="Search area, street or landmark" autocomplete="off"></div>
                            <div id="freshful-address-map" class="freshful-address-map" role="application" aria-label="Choose delivery location on map"></div>
                            <div class="d-flex justify-content-between align-items-center gap-2 mt-2"><small id="freshful-address-map-status" class="text-muted">Click the map to place the pin.</small><button type="button" class="btn btn-sm btn-outline-success" data-freshful-use-location><i class="bi bi-crosshair me-1"></i>Use my location</button></div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-3">
                                <div class="col-12"><label class="form-label fw-semibold">Full Name *</label><input type="text" name="name" class="form-control" required></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Phone *</label><input type="tel" name="phone" class="form-control" required></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Address Type *</label><select name="type" class="form-select" required><option value="Home">Home</option><option value="Work">Work</option><option value="Other">Other</option></select></div>
                                <div class="col-12"><label class="form-label fw-semibold">Area / Locality *</label><input type="text" name="locality" class="form-control" required></div>
                                <div class="col-12"><label class="form-label fw-semibold">Flat / Building / Street *</label><input type="text" name="street_name" class="form-control" required></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">Landmark</label><input type="text" name="landmark" class="form-control"></div>
                                <div class="col-md-6"><label class="form-label fw-semibold">City *</label><input type="text" name="city" class="form-control" required></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger fw-bold" data-address-save>Save Address</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Promocode Modal -->
<div class="modal fade" id="promocode" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ff-promo-modal">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Available offers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group ff-promo-input">
                    <input type="text" id="promo_code" class="form-control" placeholder="Enter promo code">
                    <button class="btn btn-danger apply-btn" data-type="input">Apply</button>
                </div>
                <p class="small fw-bold text-uppercase text-secondary mt-3 mb-2">Available coupons</p>
                <div class="ff-promo-list">
                    @forelse($promotions as $promotion)
                    <article class="ff-promo-item">
                        <span class="ff-promo-star" aria-hidden="true">%</span>
                        <div class="ff-promo-details">
                            <strong>{{ rtrim(rtrim(number_format((float) $promotion->percentage, 2), '0'), '.') }}% Off</strong>
                            <small>{{ $promotion->description ?: ('Min. order ₹'.number_format((float) ($promotion->min_amount ?? 0), 0).(!empty($promotion->user_eligible) && $promotion->user_eligible !== 'everyone' ? ' · '.ucwords($promotion->user_eligible).' only' : '')) }}</small>
                            <span class="ff-promo-code">{{ $promotion->title }}</span>
                        </div>
                        <button type="button" class="apply-btn" data-type="coupon" data-coupon_code="{{ $promotion->title }}">Apply</button>
                    </article>
                    @empty
                    <p class="small text-muted mb-0">There are no active promo codes right now. You can still enter a code above.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
$(document).ready(function () {

    var currentHour = {{ (int) date('G') }};
    var buyerPhone  = "{{ $buyer->phone ?? '' }}";

    function resetNav() {
        $('#change_address_icon').removeClass('done');
        $('#change_delivery_summary_icon').removeClass('done');
        $('#change_address_link').addClass('d-none');
        $('#change_delivery_link').addClass('d-none');
    }

    // Step 1 → Step 2
    $('#saved_address_btn').click(function () {
        $('#saved_address').hide();
        $('#delivery_summary').show();
        $('#change_address_icon').addClass('done');
        $('#change_address_link').removeClass('d-none');
    });

    // Step 2 → Step 1
    $('#change_address_btn').click(function () {
        $('#delivery_summary').hide();
        $('#saved_address').show();
        $('#change_address_icon').removeClass('done');
        $('#change_address_link').addClass('d-none');
    });

    // Step 2 → Step 3
    $('#delivery_summary_btn').click(function () {
        $('#delivery_summary').hide();
        $('#payment_page').show();
        $('#change_delivery_summary_icon').addClass('done');
        $('#change_delivery_link').removeClass('d-none');
        $('#payment_method_icon').addClass('done');
    });

    // Step 3 → Step 2
    $('#change_delivery_btn').click(function () {
        $('#payment_page').hide();
        $('#delivery_summary').show();
        $('#change_delivery_summary_icon').removeClass('done');
        $('#change_delivery_link').addClass('d-none');
        $('#payment_method_icon').removeClass('done');
    });

    // Payment option info panels
    $('input[name=payment_type]').change(function () {
        $('#payment_cod_info, #payment_online_info, #payment_wallet_info').hide();
        var val = $(this).val();
        if (val === 'Cash')   $('#payment_cod_info').show();
        if (val === 'Online') $('#payment_online_info').show();
        if (val === 'Wallet') $('#payment_wallet_info').show();
    });

    // Slot modal
    $('#time_button').click(function () {
        new bootstrap.Modal(document.getElementById('myModal')).show();
    });

    // Today / Tomorrow toggle
    $('.day_time').click(function () {
        $('.day_time').removeClass('active_time btn-danger').addClass('btn-outline-danger');
        $(this).addClass('active_time btn-danger').removeClass('btn-outline-danger');
        var dayType = $(this).data('day_type');
        if (dayType === 'tomorrow') {
            var d = new Date(); d.setDate(d.getDate() + 1);
            $('#schedule_date').val(d.toISOString().slice(0,10));
            $('#slot_day_label').text($(this).text());
            $('.slot-time .schedule_time').removeClass('disable_click');
        } else {
            $('#schedule_date').val(new Date().toISOString().slice(0,10));
            $('#slot_day_label').text($(this).text());
            $('.slot-time .schedule_time').each(function () {
                var h = parseInt($(this).attr('id').replace('time_', ''));
                if (h <= currentHour) $(this).addClass('disable_click');
                else $(this).removeClass('disable_click');
            });
        }
    });

    // Select slot
    $('.schedule_time').click(function () {
        if ($(this).hasClass('disable_click')) return;
        var sch_time = $(this).data('time');
        var sch_type = $(this).data('type');
        $('#schedule_time').val(sch_time);
        $('#delivery_type').val(sch_type);
        $('#time_button_text').text($(this).data('label') || ($('#slot_day_label').text().split(' (')[0] + ' · ' + sch_time));
        $('.schedule_time').removeClass('active_time');
        $(this).addClass('active_time');
        bootstrap.Modal.getInstance(document.getElementById('myModal')).hide();
    });

    // Place Order
    $('#place_order').click(function () {
        var address_id    = $('input[name=address_id]:checked').val();
        var payment_type  = $('input[name=payment_type]:checked').val();
        var delivery_type = $('#delivery_type').val();
        var delivery_charge = {{ $delivery_charge }};
        var schedule_date = $('#schedule_date').val();
        var schedule_time = $('#schedule_time').val();
        var hub_id        = "{{ $hub_id }}";
        var is_promo      = $(this).data('promo');
        var promocode     = is_promo === 'yes' ? $(this).data('code') : '';
        var total_amnt    = parseInt($('#sub_total').data('amount'));

        if (!address_id) { notify('Please select a delivery address', 'warning'); return; }
        if (!schedule_time) { notify('Please select a delivery slot', 'warning'); return; }

        var data = {
            _token:         "{{ csrf_token() }}",
            address_id:     address_id,
            payment_type:   payment_type,
            delivery_type:  delivery_type,
            delivery_charge: delivery_charge,
            schedule_date:  schedule_date,
            schedule_time:  schedule_time,
            hub_id:         hub_id,
            is_promo:       is_promo,
            promocode:      promocode
        };

        if (payment_type === 'Online') {
            PayOnline(total_amnt, data, buyerPhone);
        } else {
            sendAjaxCall(data);
        }
    });

    // Promocode
    $('.apply-btn').click(function () {
        var promo_code = $(this).data('type') === 'input' ? $('#promo_code').val() : $(this).data('coupon_code');
        if (!promo_code) { notify('Enter a promo code', 'warning'); return; }

        var sub_total = {{ $cart_total }};
        var delivery_charge = {{ $delivery_charge }};

        $.ajax({
            type: 'POST',
            url: "{{ url('/ajax/apply_promocode') }}",
            data: { _token: "{{ csrf_token() }}", promo_code: promo_code },
            dataType: 'json',
            success: function (res) {
                if (res.result) {
                    var discount    = sub_total * parseInt(res.discount) / 100;
                    var after_apply = sub_total - discount + delivery_charge;
                    $('.grand_total').text('₹' + after_apply);
                    $('#sub_total').data('amount', after_apply);
                    $('.discounttt').text('- ₹' + discount);
                    $('#promocode').modal('hide');
                    $('#place_order').attr('data-promo', 'yes').attr('data-code', promo_code);
                    notify('Promo applied! You saved ₹' + discount, 'success');
                } else {
                    notify(res.message || 'Invalid promo code', 'error');
                }
            }
        });
    });
});

function PayOnline(amount, data, mobile) {
    var options = {
        key: "rzp_live_TpqyzOC5QrPIKS",
        amount: amount * 100,
        currency: "INR",
        name: "Freshful",
        description: "Online Payment",
        image: "{{ asset('assets/image/logo.png') }}",
        prefill: { contact: mobile },
        theme: { color: "#e41d36" },
        handler: function (response) {
            data.payment_id = response.razorpay_payment_id;
            sendAjaxCall(data);
        },
        modal: {
            ondismiss: function () {
                notify('Payment cancelled', 'warning');
            }
        }
    };
    var rzp = new Razorpay(options);
    rzp.open();
}

function sendAjaxCall(data) {
    $.ajax({
        url: "{{ route('add-order') }}",
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function (response) {
            if (response.result) {
                notify(response.message || 'Order placed successfully!', 'success');
                setTimeout(function () {
                    window.location.href = "{{ route('myaccount') }}";
                }, 1500);
            } else {
                notify(response.message || 'Something went wrong', 'error');
            }
        },
        error: function () {
            notify('Network error. Please try again.', 'error');
        }
    });
}
</script>
@endpush

@endsection
