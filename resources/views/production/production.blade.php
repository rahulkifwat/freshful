@extends('production.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Production</h4>
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
                        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Hub</th>
                                <th>Produced Qty</th>
                                <th>Production Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $i => $row)
                            <tr>
                                <td>{{ $rows->firstItem() + $i }}</td>
                                <td>{{ $row->product_name ?? '-' }}</td>
                                <td>{{ $row->sku ?? '-' }}</td>
                                <td>{{ $row->hub_name ?? '-' }}</td>
                                <td>{{ $row->quantity }}</td>
                                <td>{{ $row->production_date ?? $row->created_at ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-{{ $row->status == 'completed' ? 'success' : 'warning' }}">
                                        {{ ucfirst($row->status ?? 'pending') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center">No production records found</td></tr>
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
