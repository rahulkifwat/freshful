@extends('production.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">InterHub Moments</h4>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-body">
                <form method="GET" class="row mb-3">
                    <div class="col-md-3 form-group">
                        <label>From Hub</label>
                        <select name="from_hub" class="form-control">
                            <option value="">All</option>
                            @foreach($hubs as $h)
                            <option value="{{ $h->id }}" {{ request('from_hub') == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>To Hub</label>
                        <select name="to_hub" class="form-control">
                            <option value="">All</option>
                            @foreach($hubs as $h)
                            <option value="{{ $h->id }}" {{ request('to_hub') == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All</option>
                            <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                            <option value="completed" {{ request('status')=='completed'?'selected':'' }}>Completed</option>
                        </select>
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
                                <th>From Hub</th>
                                <th>To Hub</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $i => $row)
                            <tr>
                                <td>{{ $rows->firstItem() + $i }}</td>
                                <td>{{ $row->product_name ?? '-' }}</td>
                                <td>{{ $row->from_hub_name ?? '-' }}</td>
                                <td>{{ $row->to_hub_name ?? '-' }}</td>
                                <td>{{ $row->quantity }}</td>
                                <td>
                                    <span class="badge badge-{{ $row->status == 'completed' ? 'success' : 'warning' }}">
                                        {{ ucfirst($row->status ?? 'pending') }}
                                    </span>
                                </td>
                                <td>{{ $row->created_at ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center">No interhub moments found</td></tr>
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
