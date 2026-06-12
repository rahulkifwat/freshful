@extends('operation_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Cash Deposits - Hub Wise</h4>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-body">
                <form method="GET" class="row mb-3">
                    <div class="col-md-3 form-group">
                        <label>Hub</label>
                        <select name="hub_id" class="form-control">
                            <option value="">Select Hub</option>
                            @foreach($hubs as $h)
                            <option value="{{ $h->id }}" {{ request('hub_id')==$h->id?'selected':'' }}>{{ $h->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-3 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-search"></i> Filter</button>
                        <a href="{{ route('operation_manager.view_cash_deposits_hub') }}" class="btn btn-secondary"><i class="fa fa-refresh"></i></a>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Hub</th>
                                <th>Amount</th>
                                <th>Bank</th>
                                <th>Transaction No</th>
                                <th>Deposit Date</th>
                                <th>Receipt</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deposits as $i => $row)
                            <tr>
                                <td>{{ $deposits->firstItem() + $i }}</td>
                                <td>{{ $row->hub_name ?? '-' }}</td>
                                <td>₹{{ number_format($row->amount, 2) }}</td>
                                <td>{{ $row->bank_name ?? '-' }}</td>
                                <td>{{ $row->transaction_no ?? '-' }}</td>
                                <td>{{ $row->deposit_date ?? '-' }}</td>
                                <td>
                                    @if(!empty($row->receipt_image))
                                        <a href="{{ asset('uploads/receipts/'.$row->receipt_image) }}" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-image"></i> View</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $row->status=='verified'?'success':($row->status=='rejected'?'danger':'warning') }}">
                                        {{ ucfirst($row->status ?? 'pending') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center">No deposits found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $deposits->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
