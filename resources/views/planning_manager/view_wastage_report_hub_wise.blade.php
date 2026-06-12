@extends('planning_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Wastage Report - Hub Wise</h4>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-body">
                <form method="GET" class="row mb-3">
                    <div class="col-md-3 form-group">
                        <label>Hub</label>
                        <select name="hub_id" class="form-control">
                            <option value="">All Hubs</option>
                            @foreach($hubs as $h)
                            <option value="{{ $h->id }}" {{ request('hub_id') == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
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
                        <a href="{{ route('planning_manager.view_wastage_report_hub_wise') }}" class="btn btn-secondary"><i class="fa fa-refresh"></i></a>
                    </div>
                </form>
                @foreach($hubs_data as $hub)
                <div class="card mb-3 border-left-warning">
                    <div class="card-header bg-warning text-white"><b>{{ $hub->hub_name }}</b> — Total Wastage: {{ $hub->total_wastage }}</div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-bordered mb-0">
                            <thead><tr><th>Product</th><th>Quantity</th><th>Reason</th><th>Date</th></tr></thead>
                            <tbody>
                                @foreach($hub->items as $item)
                                <tr>
                                    <td>{{ $item->product_name ?? '-' }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ $item->reason ?? '-' }}</td>
                                    <td>{{ $item->wastage_date ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
                @if(empty($hubs_data))
                <div class="alert alert-info">No wastage data found for the selected filters.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
