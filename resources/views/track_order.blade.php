@extends('layout.master')
@section('content')

<style>
.order-status-header { background:#fff; border-radius:8px; box-shadow:0 0 13px rgba(0,0,0,.1); padding:24px; margin-bottom:24px; }
.product-card { background:#fff; border-radius:8px; box-shadow:0 0 8px rgba(0,0,0,.08); padding:24px; margin-bottom:20px; }
.rating-stars ul { list-style:none; padding:0; margin:0 0 10px; user-select:none; }
.rating-stars ul li { display:inline-block; cursor:pointer; }
.rating-stars ul li i { font-size:1.6rem; color:#ccc; }
.rating-stars ul li.hover i,
.rating-stars ul li.selected i { color:#ffcc36; }
</style>

<main class="py-5">
    <div class="container">
        <div class="d-flex align-items-center mb-4 gap-3">
            <a href="{{ route('myaccount') }}" class="btn btn-outline-secondary btn-sm">← My Orders</a>
            <h4 class="mb-0 fw-bold">Order Details</h4>
        </div>

        <!-- Order Status Header -->
        <div class="order-status-header">
            @php
                $first = $order_items->first();
                $status_colors = [
                    'Order Pending'    => ['bg'=>'#858585','fg'=>'#fff'],
                    'Order Cancel'     => ['bg'=>'#e52121','fg'=>'#fff'],
                    'Order Placed'     => ['bg'=>'#e0389b','fg'=>'#fff'],
                    'Order Processed'  => ['bg'=>'#fa8d12','fg'=>'#fff'],
                    'Order Shipped'    => ['bg'=>'#099cc0','fg'=>'#fff'],
                    'Order Delivered'  => ['bg'=>'#17cf1c','fg'=>'#fff'],
                    'Order Dispatched' => ['bg'=>'#f3db1b','fg'=>'#000'],
                ];
                $color = $status_colors[$first->order_status] ?? ['bg'=>'#858585','fg'=>'#fff'];
            @endphp
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-1">
                        <span class="px-3 py-1 rounded fw-bold"
                              style="background:{{ $color['bg'] }};color:{{ $color['fg'] }}">
                            {{ $first->order_status }}
                        </span>
                    </h5>
                    <p class="mb-0 text-muted mt-2">
                        Dear customer, your Order ID is <strong class="text-danger">{{ $order_id }}</strong>
                    </p>
                </div>
                <div class="text-end">
                    <p class="mb-0 small text-muted">Placed on {{ date('d M Y, h:i A', strtotime($first->date_added)) }}</p>
                    <p class="mb-0 small text-muted">
                        Delivery: {{ $first->delivery_type === 'Express' ? 'Express' : $first->schedule_time }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Product List -->
        <div class="row g-4">
            @foreach($order_items as $item)
            @php
                $existing_review = $reviews[$item->product_id] ?? null;
                $existing_rating = $existing_review?->rating ?? 0;
                $existing_feedback = $existing_review?->feedback ?? '';
            @endphp
            <div class="col-12">
                <div class="product-card">
                    <div class="row g-3">
                        <!-- Product Info -->
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <img src="{{ asset('uploads/images/products/' . $item->product_image) }}"
                                     alt="{{ $item->item_name }}"
                                     style="width:120px;height:120px;object-fit:cover;border-radius:8px;">
                                <div>
                                    <h5 class="mb-1 fw-bold">{{ $item->item_name }}</h5>
                                    <p class="mb-1 text-muted small">
                                        <img src="{{ asset('assets/image/weight.png') }}" width="18" class="me-1">
                                        {{ $item->unit_quantity }}{{ $item->product_unit }}
                                        &nbsp;&nbsp; <strong>Qty:</strong> {{ $item->quantity }}
                                    </p>
                                    <h5 class="text-danger mb-0">₹{{ $item->main_price }}</h5>
                                </div>
                            </div>
                        </div>

                        <!-- Review Form -->
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2">{{ $existing_review ? 'Your Review' : 'Submit A Review' }}</h6>
                            <form class="review-form">
                                <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                                <div class="rating-stars mb-2">
                                    <ul id="stars_{{ $item->product_id }}">
                                        @for($s = 1; $s <= 5; $s++)
                                        <li class="star {{ $existing_rating >= $s ? 'selected' : '' }}"
                                            data-value="{{ $s }}"
                                            data-product="{{ $item->product_id }}">
                                            <i class="bi bi-star-fill"></i>
                                        </li>
                                        @endfor
                                    </ul>
                                    <input type="hidden" name="rating" class="rating-value" value="{{ $existing_rating }}">
                                </div>
                                <div class="mb-2">
                                    <textarea name="feedback" class="form-control" rows="3"
                                              placeholder="Share your experience...">{{ $existing_feedback }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-sm btn-danger">
                                    {{ $existing_review ? 'Update Review' : 'Save Review' }}
                                </button>
                                <span class="review-msg ms-2 small"></span>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Bill Summary -->
        <div class="card shadow-sm mt-3 p-4">
            @php
                $items_total = $order_items->sum(fn($i) => $i->quantity * $i->main_price);
                $delivery    = $first->delivery_charge ?? 0;
            @endphp
            <h6 class="fw-bold border-bottom pb-2 mb-3">Payment Summary</h6>
            <div class="d-flex justify-content-between mb-1"><span>Items Total</span><span>₹{{ $items_total }}</span></div>
            <div class="d-flex justify-content-between mb-1"><span>Delivery Charge</span><span>₹{{ $delivery }}</span></div>
            <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-1">
                <span>Grand Total</span><span class="text-danger">₹{{ $items_total + $delivery }}</span>
            </div>
            <p class="small text-muted mt-2 mb-0">Payment: {{ $first->payment_type }}</p>
        </div>
    </div>
</main>

@push('scripts')
<script>
$(document).ready(function () {

    // Star hover + select
    $('.rating-stars ul li.star')
        .on('mouseover', function () {
            var val = parseInt($(this).data('value'));
            $(this).parent().children('li.star').each(function (i) {
                if (i < val) $(this).addClass('hover'); else $(this).removeClass('hover');
            });
        })
        .on('mouseout', function () {
            $(this).parent().children('li.star').removeClass('hover');
        })
        .on('click', function () {
            var val = parseInt($(this).data('value'));
            var $ul = $(this).parent();
            $ul.children('li.star').each(function (i) {
                if (i < val) $(this).addClass('selected'); else $(this).removeClass('selected');
            });
            $ul.closest('.rating-stars').find('.rating-value').val(val);
        });

    // Review form submit
    $('.review-form').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $msg  = $form.find('.review-msg');
        var data  = {
            _token:     '{{ csrf_token() }}',
            product_id: $form.find('[name=product_id]').val(),
            rating:     $form.find('.rating-value').val(),
            feedback:   $form.find('[name=feedback]').val()
        };

        $.post('{{ route("add-review") }}', data, function (res) {
            if (res.result) {
                $msg.removeClass('text-danger').addClass('text-success').text(res.message);
                $form.find('button[type=submit]').text('Update Review');
            } else {
                $msg.removeClass('text-success').addClass('text-danger').text(res.message);
            }
        }, 'json').fail(function () {
            $msg.addClass('text-danger').text('Network error');
        });
    });
});
</script>
@endpush

@endsection
