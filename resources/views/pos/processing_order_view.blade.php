@extends('pos.layout.master')
@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Order #{{ $order->order_id }}</h4>
                            <div>
                                <a href="{{ route('pos.invoice', $order->order_id) }}" class="btn btn-info btn-sm" target="_blank">Print Invoice</a>
                                <a href="{{ route('pos.unsettled_invoices') }}" class="btn btn-secondary btn-sm">Back</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <strong>Order Status:</strong>
                                    <span class="badge badge-primary ml-2">{{ $order->order_status }}</span>
                                </div>
                                <div class="col-sm-6">
                                    <strong>Payment Status:</strong>
                                    <span class="badge badge-{{ $order->payment_status == 'paid' ? 'success' : 'warning' }} ml-2">{{ $order->payment_status ?? 'Pending' }}</span>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <strong>Order Date:</strong> {{ \Carbon\Carbon::parse($order->date_added)->format('d-m-Y h:i a') }}
                                </div>
                                <div class="col-sm-6">
                                    <strong>Delivery Type:</strong> {{ $order->delivery_type }}
                                    @if($order->schedule_time) &nbsp;({{ $order->schedule_time }}) @endif
                                </div>
                            </div>

                            <h5 class="mt-4">Order Items</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th><th>Product</th><th>SKU</th>
                                            <th>Qty</th><th>Unit Price</th><th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $i => $item)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $item->product_name ?? $item->name }}</td>
                                            <td>{{ $item->sku ?? '-' }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>₹{{ $item->unit_price ?? $item->price }}</td>
                                            <td>₹{{ $item->total_price ?? ($item->quantity * $item->price) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-right"><strong>Sub Total</strong></td>
                                            <td>₹{{ $order->sub_total ?? $order->total_amount }}</td>
                                        </tr>
                                        @if(isset($order->discount_amount) && $order->discount_amount > 0)
                                        <tr>
                                            <td colspan="5" class="text-right text-success"><strong>Discount</strong></td>
                                            <td class="text-success">-₹{{ $order->discount_amount }}</td>
                                        </tr>
                                        @endif
                                        @if(isset($promo) && $promo)
                                        <tr>
                                            <td colspan="5" class="text-right text-info"><strong>Promo ({{ $promo->promo_code }})</strong></td>
                                            <td class="text-info">-₹{{ $order->promo_discount ?? 0 }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td colspan="5" class="text-right"><strong>Grand Total</strong></td>
                                            <td><strong>₹{{ $order->total_amount }}</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            @if($order->order_status == 'Order Accepted' || $order->order_status == 'Order Shipped')
                            <hr>
                            <h5>Update Order Status</h5>
                            <form id="statusForm">
                                @csrf
                                <input type="hidden" name="order_id" value="{{ $order->order_id }}">
                                <div class="input-group">
                                    <select name="order_status" class="form-control">
                                        <option value="Order Accepted" {{ $order->order_status == 'Order Accepted' ? 'selected' : '' }}>Order Accepted</option>
                                        <option value="Order Shipped" {{ $order->order_status == 'Order Shipped' ? 'selected' : '' }}>Order Shipped</option>
                                        <option value="Order Processed" {{ $order->order_status == 'Order Processed' ? 'selected' : '' }}>Order Processed</option>
                                        <option value="Order Delivered" {{ $order->order_status == 'Order Delivered' ? 'selected' : '' }}>Order Delivered</option>
                                        <option value="Order Cancel">Order Cancel</option>
                                    </select>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary" id="updateStatusBtn">Update</button>
                                    </div>
                                </div>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header"><h5 class="mb-0">Customer Info</h5></div>
                        <div class="card-body">
                            <p><strong>Name:</strong> {{ $buyer->name ?? $order->name }}</p>
                            <p><strong>Phone:</strong> {{ $buyer->phone ?? $order->phone }}</p>
                            <p><strong>Email:</strong> {{ $buyer->email ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-header"><h5 class="mb-0">Delivery Address</h5></div>
                        <div class="card-body">
                            @if($address)
                            <p>{{ $address->address_line1 ?? '' }}</p>
                            <p>{{ $address->address_line2 ?? '' }}</p>
                            <p>{{ $address->city ?? '' }} {{ $address->pincode ?? '' }}</p>
                            <p><strong>Landmark:</strong> {{ $address->landmark ?? '-' }}</p>
                            @else
                            <p class="text-muted">No address info</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
$('#updateStatusBtn').click(function(){
    var data = $('#statusForm').serialize();
    $.ajax({
        url: "{{ route('pos.update_order_status') }}",
        method: 'POST',
        data: data,
        success: function(res){
            if(res.status === 'success'){
                toastr.success(res.message || 'Status updated');
                setTimeout(function(){ location.reload(); }, 1000);
            } else {
                toastr.error(res.message || 'Failed to update status');
            }
        },
        error: function(){
            toastr.error('Server error');
        }
    });
});
</script>
@endpush
@endsection
