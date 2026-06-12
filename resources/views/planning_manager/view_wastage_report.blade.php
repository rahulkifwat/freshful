@extends('planning_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">View Wastage Report</h4>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-body">
                <form method="GET" class="row mb-3">
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
                        <a href="{{ route('planning_manager.view_wastage_report') }}" class="btn btn-secondary"><i class="fa fa-refresh"></i></a>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Hub</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Reason</th>
                                <th>Date</th>
                                <th>Reported By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $i => $row)
                            <tr>
                                <td>{{ $rows->firstItem() + $i }}</td>
                                <td>{{ $row->hub_name ?? '-' }}</td>
                                <td>{{ $row->product_name ?? '-' }}</td>
                                <td>{{ $row->quantity }}</td>
                                <td>{{ $row->reason ?? '-' }}</td>
                                <td>{{ $row->wastage_date ?? $row->created_at ?? '-' }}</td>
                                <td>{{ $row->reported_by ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center">No wastage reports found</td></tr>
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
