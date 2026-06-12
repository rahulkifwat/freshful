@extends('pos.layout.master')
@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Settle Order #{{ $order->order_id }}</h4>
                            <a href="{{ route('pos.unsettled_invoices') }}" class="btn btn-secondary btn-sm">Back</a>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <strong>Customer:</strong> {{ $buyer->name ?? $order->name }}<br>
                                    <strong>Phone:</strong> {{ $buyer->phone ?? $order->phone }}
                                </div>
                                <div class="col-sm-6">
                                    <strong>Order Date:</strong> {{ \Carbon\Carbon::parse($order->date_added)->format('d-m-Y h:i a') }}<br>
                                    <strong>Delivery Type:</strong> {{ $order->delivery_type }}
                                </div>
                            </div>

                            <h5>Order Items</h5>
                            <div class="table-responsive mb-3">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $item)
                                        <tr>
                                            <td>{{ $item->product_name ?? $item->name }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>₹{{ $item->unit_price ?? $item->price }}</td>
                                            <td>₹{{ $item->total_price ?? ($item->quantity * $item->price) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col-sm-4 text-center">
                                        <h5>Grand Total</h5>
                                        <h3 class="text-primary">₹{{ $order->total_amount }}</h3>
                                    </div>
                                    <div class="col-sm-4 text-center">
                                        @if($amount_to_collect > 0)
                                        <h5>Amount to Collect</h5>
                                        <h3 class="text-success">₹{{ $amount_to_collect }}</h3>
                                        @else
                                        <h5>Amount to Return</h5>
                                        <h3 class="text-danger">₹{{ $amount_to_return }}</h3>
                                        @endif
                                    </div>
                                    <div class="col-sm-4 text-center">
                                        <h5>Payment Method</h5>
                                        <h3>{{ $order->payment_method ?? 'COD' }}</h3>
                                    </div>
                                </div>
                            </div>

                            <form id="settleForm">
                                @csrf
                                <input type="hidden" name="order_id" value="{{ $order->order_id }}">
                                <input type="hidden" name="order_status" value="Order Delivered">
                                <div class="text-center mt-3">
                                    <button type="button" class="btn btn-success btn-lg" id="settleBtn">
                                        Mark as Settled / Delivered
                                    </button>
                                    <a href="{{ route('pos.invoice', $order->order_id) }}" class="btn btn-info btn-lg ml-2" target="_blank">
                                        Print Invoice
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
$('#settleBtn').click(function(){
    if(!confirm('Mark this order as delivered and settled?')) return;
    $.ajax({
        url: "{{ route('pos.update_order_status') }}",
        method: 'POST',
        data: $('#settleForm').serialize(),
        success: function(res){
            if(res.status === 'success'){
                toastr.success('Order settled successfully!');
                setTimeout(function(){ window.location="{{ route('pos.unsettled_invoices') }}"; }, 1200);
            } else {
                toastr.error(res.message || 'Failed to settle order');
            }
        },
        error: function(){ toastr.error('Server error'); }
    });
});
</script>
@endpush
@endsection
