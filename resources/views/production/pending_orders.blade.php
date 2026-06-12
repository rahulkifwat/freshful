@extends('production.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Pending Orders</h4>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Hub</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $i => $row)
                        <tr>
                            <td>{{ $orders->firstItem() + $i }}</td>
                            <td>#{{ $row->id }}</td>
                            <td>{{ $row->customer_name ?? '-' }}</td>
                            <td>{{ $row->hub_name ?? '-' }}</td>
                            <td>₹{{ number_format($row->total_amount, 2) }}</td>
                            <td>{{ ucfirst($row->order_type ?? 'normal') }}</td>
                            <td>{{ $row->created_at ?? '-' }}</td>
                            <td>
                                <a href="{{ route('production.view_order', $row->id) }}" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> View</a>
                                <select class="btn btn-sm btn-warning change_order_status mt-1" data-id="{{ $row->id }}">
                                    <option value="placed">Placed</option>
                                    <option value="confirmed">Confirm</option>
                                    <option value="cancelled">Cancel</option>
                                </select>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center">No pending orders</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $orders->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
