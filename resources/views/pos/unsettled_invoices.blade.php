@extends('pos.layout.master')
@section('content')
<div class="content-page">
    <div class="content">
        <h3>Unsettled Invoices</h3>
        <p class="text-muted">Orders delivered but payment not yet collected.</p>
        <div class="table-responsive">
            <table class="table mb-0 table-bordered table-striped" id="unsettled_table">
                <thead>
                    <tr>
                        <th>Date</th><th>Order Id</th><th>Customer</th><th>Mobile</th>
                        <th>Hub</th><th>Amount</th><th>Payment Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $v)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($v->date_added)->format('d-m-Y h:i a') }}</td>
                        <td>{{ $v->order_id }}</td>
                        <td>{{ $v->name }}</td>
                        <td>{{ $v->phone }}</td>
                        <td>{{ $v->hub }}</td>
                        <td>₹{{ $v->total_amount }}</td>
                        <td><span class="badge badge-warning">{{ $v->payment_status ?? 'Pending' }}</span></td>
                        <td>
                            <a href="{{ route('pos.settle_order', $v->order_id) }}" class="btn btn-success btn-sm">Settle</a>
                            <a href="{{ route('pos.invoice', $v->order_id) }}" class="btn btn-info btn-sm" target="_blank">Invoice</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center">No unsettled invoices</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@push('scripts')
<script>$(document).ready(function(){ $('#unsettled_table').DataTable({order:[[0,'asc']]}); });</script>
@endpush
@endsection
