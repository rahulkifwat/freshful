@extends('operation_manager.layout.master')
@section('content')
<div class="right-panel">
    <div class="content">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="page-title">Customer Orders</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('operation_manager.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Customer Orders</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('operation_manager.customer_order') }}" class="form-inline">
                        <div class="form-group mr-2">
                            <label class="mr-1">Buyer ID</label>
                            <input type="number" name="buyer_id" class="form-control" value="{{ $buyer_id }}" placeholder="Enter buyer ID" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
            </div>

            @if($buyer)
                <div class="card mb-3">
                    <div class="card-header"><h5>Buyer Info</h5></div>
                    <div class="card-body">
                        <p><strong>Name:</strong> {{ $buyer->name ?? '—' }}</p>
                        <p><strong>Phone:</strong> {{ $buyer->phone ?? '—' }}</p>
                        <p><strong>Email:</strong> {{ $buyer->email ?? '—' }}</p>
                        <p><strong>Address:</strong> {{ $buyer->address ?? '—' }}</p>
                    </div>
                </div>
            @elseif($buyer_id)
                <div class="alert alert-warning">No buyer found with ID {{ $buyer_id }}.</div>
            @endif

            @if($buyer && $orders->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Orders ({{ $orders->total() }})</h4></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Total (₹)</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($orders as $order)
                                    <tr>
                                        <td>{{ $order->order_id }}</td>
                                        <td>₹{{ number_format($order->total_price, 2) }}</td>
                                        <td>
                                            <select class="form-control form-control-sm change_status_order"
                                                data-id="{{ $order->id }}" data-order_id="{{ $order->order_id }}">
                                                @foreach(['Order Placed','Order Confirmed','Out for Delivery','Delivered','Cancelled'] as $s)
                                                    <option value="{{ $s }}" {{ $order->order_status == $s ? 'selected' : '' }}>{{ $s }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>{{ $order->created_at }}</td>
                                        <td>
                                            <a href="{{ route('operation_manager.view_order', ['order_id' => $order->order_id]) }}"
                                               class="btn btn-sm btn-info"><i class="fa fa-eye"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $orders->appends(request()->query())->links() }}
                    </div>
                </div>
            @elseif($buyer)
                <div class="alert alert-info">No orders found for this buyer.</div>
            @endif
        </div>
    </div>
</div>
@endsection
