@extends('production.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Cash Deposits - All Hubs</h4>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-body">
                <form method="GET" class="row mb-3">
                    <div class="col-md-4 form-group">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-4 form-group d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-search"></i> Filter</button>
                        <a href="{{ route('production.view_cash_deposits_all_hub') }}" class="btn btn-secondary"><i class="fa fa-refresh"></i></a>
                    </div>
                </form>
                @foreach($hubs_data as $hub)
                <div class="card mb-3 border-left-primary">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <b>{{ $hub->hub_name }}</b>
                        <span class="badge badge-primary">Total: ₹{{ number_format($hub->total_amount, 2) }}</span>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered mb-0">
                            <thead><tr><th>Amount</th><th>Bank</th><th>Transaction No</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($hub->deposits as $dep)
                                <tr>
                                    <td>₹{{ number_format($dep->amount, 2) }}</td>
                                    <td>{{ $dep->bank_name ?? '-' }}</td>
                                    <td>{{ $dep->transaction_no ?? '-' }}</td>
                                    <td>{{ $dep->deposit_date ?? '-' }}</td>
                                    <td><span class="badge badge-{{ $dep->status=='verified'?'success':($dep->status=='rejected'?'danger':'warning') }}">{{ ucfirst($dep->status ?? 'pending') }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
                @if(empty($hubs_data))
                <div class="alert alert-info">No deposit records found.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
