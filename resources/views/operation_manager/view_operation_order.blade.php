@extends('operation_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="page-title">View Operation Order #{{ $order->id }}</h4>
                <a href="{{ route('operation_manager.all_orders') }}" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header"><b>Order Information</b></div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr><th>Order ID</th><td>#{{ $order->id }}</td></tr>
                            <tr><th>Customer</th><td>{{ $order->customer_name ?? '-' }}</td></tr>
                            <tr><th>Phone</th><td>{{ $order->phone ?? '-' }}</td></tr>
                            <tr><th>Hub</th><td>{{ $order->hub_name ?? '-' }}</td></tr>
                            <tr><th>Order Type</th><td>{{ ucfirst($order->order_type ?? 'normal') }}</td></tr>
                            <tr><th>Status</th><td>
                                <span class="badge badge-{{ $order->order_status=='delivered'?'success':($order->order_status=='cancelled'?'danger':'warning') }}">
                                    {{ ucfirst($order->order_status) }}
                                </span>
                            </td></tr>
                            <tr><th>Total Amount</th><td>₹{{ number_format($order->total_amount, 2) }}</td></tr>
                            <tr><th>Payment</th><td>{{ ucfirst($order->payment_method ?? '-') }}</td></tr>
                            <tr><th>Order Date</th><td>{{ $order->created_at }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header"><b>Delivery Address</b></div>
                    <div class="card-body">
                        <p>{{ $order->delivery_address ?? '-' }}</p>
                        <p><b>Delivery Boy:</b> {{ $order->delivery_boy_name ?? 'Not Assigned' }}</p>
                    </div>
                </div>
                <div class="card shadow mb-4">
                    <div class="card-header"><b>Update Status</b></div>
                    <div class="card-body">
                        <select class="form-control change_order_status" data-id="{{ $order->id }}">
                            <option value="placed" {{ $order->order_status=='placed'?'selected':'' }}>Placed</option>
                            <option value="confirmed" {{ $order->order_status=='confirmed'?'selected':'' }}>Confirmed</option>
                            <option value="dispatched" {{ $order->order_status=='dispatched'?'selected':'' }}>Dispatched</option>
                            <option value="delivered" {{ $order->order_status=='delivered'?'selected':'' }}>Delivered</option>
                            <option value="cancelled" {{ $order->order_status=='cancelled'?'selected':'' }}>Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-header"><b>Order Items</b></div>
            <div class="card-body table-responsive">
                <table class="table table-bordered">
                    <thead class="thead-dark">
                        <tr><th>#</th><th>Product</th><th>SKU</th><th>Qty</th><th>Price</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        @forelse($items as $i => $item)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>{{ $item->product_name ?? '-' }}</td>
                            <td>{{ $item->sku ?? '-' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>₹{{ number_format($item->price, 2) }}</td>
                            <td>₹{{ number_format($item->quantity * $item->price, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">No items</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
