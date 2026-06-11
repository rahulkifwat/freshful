@extends('hub.layout.master')
@section('content')
<div class="right-panel">
    <div class="content">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="page-title">Today's Orders</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('hub.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Today's Orders</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Orders — {{ now()->format('d M Y') }}</h4></div>
                <div class="card-body">
                    @if($orders->isEmpty())
                        <div class="alert alert-info">No orders today.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>Order ID</th><th>Buyer</th><th>Phone</th><th>Total (₹)</th><th>Status</th><th>Time</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                @foreach($orders as $order)
                                    <tr>
                                        <td>{{ $order->order_id }}</td>
                                        <td>{{ $order->buyer_name ?? '—' }}</td>
                                        <td>{{ $order->buyer_phone ?? '—' }}</td>
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
                                            <a href="{{ route('hub.view_order', ['order_id' => $order->order_id]) }}"
                                               class="btn btn-sm btn-info"><i class="fa fa-eye"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $orders->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
