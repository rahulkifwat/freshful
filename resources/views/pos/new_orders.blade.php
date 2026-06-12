@extends('pos.layout.master')
@section('content')
<div class="content-page">
    <div class="content">
        <h3>New Orders</h3>
        <form method="GET" class="form-inline mb-3">
            <label class="mr-2">From:</label>
            <input type="date" name="date_from" class="form-control form-control-sm mr-2" value="{{ request('date_from') }}">
            <label class="mr-2">To:</label>
            <input type="date" name="date_to" class="form-control form-control-sm mr-2" value="{{ request('date_to') }}">
            <button type="submit" class="btn btn-primary btn-sm mr-1">Filter</button>
            <a href="{{ route('pos.new_orders') }}" class="btn btn-secondary btn-sm">Reset</a>
        </form>
        <div class="table-responsive">
            <table class="table mb-0 table-bordered table-striped" id="new_orders_table">
                <thead>
                    <tr>
                        <th>Date</th><th>Order Id</th><th>Delivery Time</th>
                        <th>Hub</th><th>Customer Name</th><th>Mobile</th>
                        <th>Status</th><th>Amount</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $v)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($v->date_added)->format('d-m-Y h:i a') }}</td>
                        <td>{{ $v->order_id }}</td>
                        <td>{{ $v->delivery_type == 'Express' ? 'Express' : ($v->schedule_time ?? 'Scheduled') }}</td>
                        <td>{{ $v->hub }}</td>
                        <td>{{ $v->name }}</td>
                        <td>{{ $v->phone }}</td>
                        <td>{{ $v->order_status }}</td>
                        <td>₹{{ $v->total_amount }}</td>
                        <td>
                            <a href="{{ route('pos.processing_order_view', $v->order_id) }}" class="btn btn-primary btn-sm" target="_blank">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center">No new orders</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@push('scripts')
<script>$(document).ready(function(){ if($('#new_orders_table tbody tr').length > 1) $('#new_orders_table').DataTable({order:[[0,'desc']}); });</script>
@endpush
@endsection
