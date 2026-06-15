@extends('layout.master')
@section('content')

<style>
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

<main class="py-3">
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
                            <label class="card p-3 d-block {{ $loop->first ? 'border-danger' : '' }}" style="cursor:pointer;">
                                <div class="d-flex align-items-start gap-2">
                                    <input type="radio" name="address_id" value="{{ $address->id }}" {{ $loop->first ? 'checked' : '' }} class="mt-1">
                                    <div>
                                        <span class="badge bg-danger mb-1">{{ $address->type }}</span>
                                        <p class="mb-0 fw-semibold">{{ $address->name ?? '' }}</p>
                                        <p class="small mb-0 text-muted">{{ $address->street_name }}, {{ $address->landmark }}, {{ $address->city }}</p>
                                        @if($address->phone ?? null)<p class="small mb-0 text-muted">{{ $address->phone }}</p>@endif
                                    </div>
                                </div>
                            </label>
                        </div>
                        @empty
                        <p class="text-muted">No saved addresses. Please add one.</p>
                        @endforelse
                        <div class="col-md-6">
                            <div class="card p-3 text-center h-100 d-flex align-items-center justify-content-center"
                                 style="border-style:dashed;cursor:pointer;min-height:80px;"
                                 data-bs-toggle="modal" data-bs-target="#add-address-modal">
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
                        <h6 class="fw-semibold">Schedule Delivery</h6>
                        <button class="btn btn-outline-danger mt-1" id="time_button">
                            <i class="bi bi-clock me-1"></i><span id="time_button_text">Select Delivery Slot</span>
                        </button>
                        <input type="hidden" id="schedule_date" value="{{ date('Y-m-d') }}">
                        <input type="hidden" id="schedule_time" value="60 min">
                        <input type="hidden" id="delivery_type" value="Express">
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
                    <button class="btn btn-outline-secondary btn-sm w-100 mb-3" id="apply_promo_btn" data-bs-toggle="modal" data-bs-target="#promocode">Apply Promocode</button>
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
                <h5 class="modal-title fw-bold" id="myModalLabel">Select Delivery Slot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
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

<!-- Add New Address Modal -->
<div class="modal fade" id="add-address-modal" tabindex="-1" aria-labelledby="addAddressLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addAddressLabel">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('storeAddress') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Phone <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" placeholder="10-digit phone number" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Locality / Area <span class="text-danger">*</span></label>
                            <input type="text" name="locality" class="form-control" placeholder="Locality or area" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Street / Flat / Building <span class="text-danger">*</span></label>
                            <input type="text" name="street_name" class="form-control" placeholder="Street, flat no., building name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Landmark <span class="text-danger">*</span></label>
                            <input type="text" name="landmark" class="form-control" placeholder="Nearby landmark" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control" placeholder="City" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Address Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <div class="form-check"><input class="form-check-input" type="radio" name="type" id="typeHome" value="Home" checked><label class="form-check-label" for="typeHome">Home</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="type" id="typeWork" value="Work"><label class="form-check-label" for="typeWork">Work</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="type" id="typeOther" value="Other"><label class="form-check-label" for="typeOther">Other</label></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Promocode Modal -->
<div class="modal fade" id="promocode" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Apply Promocode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group">
                    <input type="text" id="promo_code" class="form-control" placeholder="Enter promo code">
                    <button class="btn btn-danger apply-btn" data-type="input">Apply</button>
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
        $('#time_button_text').text(sch_time);
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
        key: "rzp_live_wHEJMOTYVkTyZn",
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
