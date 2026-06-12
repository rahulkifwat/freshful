@extends('production.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Order Status</h4>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-body">
                <form method="GET" class="row mb-3">
                    <div class="col-md-3 form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            <option value="placed" {{ request('status')=='placed'?'selected':'' }}>Placed</option>
                            <option value="confirmed" {{ request('status')=='confirmed'?'selected':'' }}>Confirmed</option>
                            <option value="dispatched" {{ request('status')=='dispatched'?'selected':'' }}>Dispatched</option>
                            <option value="delivered" {{ request('status')=='delivered'?'selected':'' }}>Delivered</option>
                            <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                    </div>
                    <div class="col-md-3 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-search"></i> Filter</button>
                        <a href="{{ route('production.order_status') }}" class="btn btn-secondary"><i class="fa fa-refresh"></i></a>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Change Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $i => $row)
                            <tr>
                                <td>{{ $orders->firstItem() + $i }}</td>
                                <td>#{{ $row->id }}</td>
                                <td>{{ $row->customer_name ?? '-' }}</td>
                                <td>₹{{ number_format($row->total_amount, 2) }}</td>
                                <td>
                                    <span class="badge badge-{{ $row->order_status=='delivered'?'success':($row->order_status=='cancelled'?'danger':'warning') }}">
                                        {{ ucfirst($row->order_status) }}
                                    </span>
                                </td>
                                <td>
                                    <select class="form-control form-control-sm change_order_status" data-id="{{ $row->id }}">
                                        <option value="placed" {{ $row->order_status=='placed'?'selected':'' }}>Placed</option>
                                        <option value="confirmed" {{ $row->order_status=='confirmed'?'selected':'' }}>Confirmed</option>
                                        <option value="dispatched" {{ $row->order_status=='dispatched'?'selected':'' }}>Dispatched</option>
                                        <option value="delivered" {{ $row->order_status=='delivered'?'selected':'' }}>Delivered</option>
                                        <option value="cancelled" {{ $row->order_status=='cancelled'?'selected':'' }}>Cancelled</option>
                                    </select>
                                </td>
                                <td>{{ $row->created_at ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center">No orders found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $orders->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
