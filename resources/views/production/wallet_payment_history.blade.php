@extends('production.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Wallet Payment History</h4>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-body">
                <form method="GET" class="row mb-3">
                    <div class="col-md-3 form-group">
                        <label>Search Customer</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name / Phone">
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
                        <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-search"></i> Search</button>
                        <a href="{{ route('production.wallet_payment_history') }}" class="btn btn-secondary"><i class="fa fa-refresh"></i></a>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $i => $row)
                            <tr>
                                <td>{{ $rows->firstItem() + $i }}</td>
                                <td>{{ $row->customer_name ?? '-' }}</td>
                                <td>{{ $row->phone ?? '-' }}</td>
                                <td class="{{ ($row->type??'debit')=='credit'?'text-success':'text-danger' }}">
                                    {{ ($row->type??'debit')=='credit' ? '+' : '-' }}₹{{ number_format($row->amount, 2) }}
                                </td>
                                <td><span class="badge badge-{{ ($row->type??'debit')=='credit'?'success':'danger' }}">{{ ucfirst($row->type ?? 'debit') }}</span></td>
                                <td>{{ $row->description ?? '-' }}</td>
                                <td>{{ $row->created_at ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center">No wallet transactions found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $rows->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
