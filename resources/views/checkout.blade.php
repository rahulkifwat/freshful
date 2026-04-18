@extends('layout.master')
@section('content')

<style>
[type="radio"]:not(:checked), [type="radio"]:checked {
    height:25px;
    width:25px;
    margin-right: 15px;
}
.li-checkout-container .li-checkout-pages .li-checkout-page .li-page-body .li-shipment-container.split:before {
    content: '';
    position: absolute;
    width: calc(100% - 20px);
    height: calc(100% - -30px);
    border: 1px solid #d47015;
    top: -28px;
    left: 10px;
    border-radius: 5px;
    z-index: -1;
}
.li-checkout-container .li-checkout-nav .li-bill-details {
    display: block;
    padding: 15px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.15);
}
/* ... existing styles from original checkout.php ... */
</style>

<main class="py-3">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <!-- Address Section -->
                <div id="saved_address" class="bg-white rounded shadow-sm p-4 mb-4">
                    <h4 class="mb-3">Choose a delivery address</h4>
                    <div class="row">
                        @foreach($addresses as $address)
                        <div class="col-md-6 mb-3">
                            <div class="card p-3 {{ $loop->first ? 'border-success' : '' }}">
                                <input type="radio" name="address_id" value="{{ $address->id }}" {{ $loop->first ? 'checked' : '' }}>
                                <h6>{{ $address->type }}</h6>
                                <p class="small mb-0">{{ $address->street_name }}, {{ $address->landmark }}, {{ $address->city }}</p>
                            </div>
                        </div>
                        @endforeach
                        <div class="col-md-6 mb-3">
                            <div class="card p-3 text-center" style="border-style: dashed; cursor: pointer;" data-toggle="modal" data-target="#add-address-modal">
                                <h6 class="mb-0 text-danger">+ ADD NEW ADDRESS</h6>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-danger mt-3" id="saved_address_btn">DELIVER HERE</button>
                </div>

                <!-- Delivery Summary Section (Hidden initially) -->
                <div id="delivery_summary" class="bg-white rounded shadow-sm p-4 mb-4" style="display:none;">
                    <h4 class="mb-3">Delivery Summary</h4>
                    <div class="li-shipment-items">
                        <ul class="list-unstyled">
                            @foreach($cart_items as $item)
                            <li class="d-flex align-items-center mb-2">
                                <img src="{{ asset('uploads/images/products/'.$item->product_image) }}" alt="{{ $item->title }}" style="width:50px; height:50px; margin-right:15px;">
                                <div>
                                    <h6 class="mb-0">{{ $item->title }}</h6>
                                    <small>Qty: {{ $item->quantity }} x ₹{{ $item->main_price }}</small>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="mt-4">
                        <h6>Schedule Delivery</h6>
                        <button class="btn btn-outline-danger" id="time_button">
                            <span id="time_button_text">Select Delivery Slot</span>
                        </button>
                        <input type="hidden" id="schedule_date" value="{{ date('Y-m-d') }}">
                        <input type="hidden" id="schedule_time" value="">
                        <input type="hidden" id="delivery_type" value="Express">
                    </div>
                    <button class="btn btn-danger mt-3" id="delivery_summary_btn">PROCEED TO PAYMENT</button>
                </div>

                <!-- Payment Section (Hidden initially) -->
                <div id="payment_page" class="bg-white rounded shadow-sm p-4 mb-4" style="display:none;">
                    <h4 class="mb-3">Payment Method</h4>
                    <div class="payment-options">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_type" id="cod" value="COD" checked>
                            <label class="form-check-label" for="cod">Cash on Delivery</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="payment_type" id="online" value="Online">
                            <label class="form-check-label" for="online">Online Payment</label>
                        </div>
                    </div>
                    <button class="btn btn-danger mt-3" id="place_order" data-promo="no" data-code="">PLACE ORDER</button>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm sticky-sidebar">
                    <div class="card-body">
                        <h6 class="fw-bold border-bottom pb-2">BILL DETAILS</h6>
                        <div class="mb-3">
                            <button class="btn btn-outline-secondary btn-sm w-100 promoCodeModal" id="apply_promo_btn">Apply Promocode</button>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Subtotal</span>
                            <span>₹{{ $cart_total }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Delivery Charge</span>
                            <span>₹{{ $delivery_charge }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 text-success">
                            <span>Discount</span>
                            <span class="discounttt">₹0</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2">
                            <span>Total</span>
                            <span class="grand_total" id="sub_total">₹{{ $cart_total + $delivery_charge }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal for Slots -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Delivery Slot</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="d-flex mb-3">
                    <button class="btn btn-danger mr-2 day_time active_time" data-day_type="today">Today</button>
                    <button class="btn btn-outline-danger day_time" data-day_type="tomorrow">Tomorrow</button>
                </div>
                <div class="slot-day text-center fw-bold mb-3">Today ({{ date('d F') }})</div>
                <ul class="list-inline slot-time text-center">
                    @php $current_hour = date('G'); @endphp
                    @for($i=7; $i<=21; $i+=2)
                        <li class="list-inline-item p-2 border mb-2 schedule_time {{ ($current_hour >= $i) ? 'disable_click' : '' }}" 
                            data-type="Scheduled" data-time="{{ $i }} AM - {{ $i+2 }} {{ $i+2 > 12 ? 'PM' : 'AM' }}">
                            {{ $i }} {{ $i >= 12 ? 'PM' : 'AM' }} - {{ $i+2 }} {{ $i+2 >= 12 ? 'PM' : 'AM' }}
                        </li>
                    @endfor
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    $(document).ready(function(){
        $('#saved_address_btn').click(function(){
            $('#delivery_summary').show();
            $('#saved_address').hide();
        });

        $('#delivery_summary_btn').click(function(){
            $('#payment_page').show();
            $('#delivery_summary').hide();
        });

        $('#time_button').click(function(){
            $('#myModal').modal('show');
        });

        $('.schedule_time').click(function(){
            if($(this).hasClass('disable_click')) return;
            var sch_time = $(this).data("time");
            var sch_type = $(this).data("type");
            $("#schedule_time").val(sch_time);
            $("#delivery_type").val(sch_type);
            $("#time_button_text").text(sch_time);
            $('.schedule_time').removeClass('active_time');
            $(this).addClass('active_time');
            $('#myModal').modal('hide');
        });

        $('#place_order').click(function(){
            var address_id = $('input[name=address_id]:checked').val();
            var payment_type = $("input[name=payment_type]:checked").val();
            var delivery_type = $("#delivery_type").val();
            var delivery_charge = {{ $delivery_charge }};
            var schedule_date = $("#schedule_date").val();
            var schedule_time = $("#schedule_time").val();
            var hub_id = "{{ $hub_id }}";

            var data = {
                _token: "{{ csrf_token() }}",
                address_id: address_id,
                payment_type: payment_type,
                delivery_type: delivery_type,
                delivery_charge: delivery_charge,
                schedule_date: schedule_date,
                schedule_time: schedule_time,
                hub_id: hub_id
            };

            if(payment_type == 'Online'){
                // Razorpay logic here
            } else {
                $.ajax({
                    url: "{{ route('add-order') }}", // Need to create this route
                    type: "POST",
                    data: data,
                    success: function(response){
                        if(response.result){
                            alert(response.message);
                            window.location.href = '/myaccount';
                        } else {
                            alert(response.message);
                        }
                    }
                });
            }
        });
    });
</script>
@endpush

@endsection
