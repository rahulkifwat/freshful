@extends('hub.layout.master')
@section('content')
<div class="right-panel">
    <div class="content">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="page-title">Accept Inward Stocks</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('hub.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Accept Inward Stocks</li>
                        </ul>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header"><h4 class="card-title">Inventory — Accept Inward</h4></div>
                <div class="card-body">
                    @if(($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty()) || (method_exists($rows, 'total') && $rows->total() == 0))
                        <div class="alert alert-info">No inventory records found for this hub.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Hub</th>
                                        <th>Stock</th>
                                        <th>Fresh Stock</th>
                                        <th>Variance</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($rows as $row)
                                    <tr>
                                        <td>{{ $row->id }}</td>
                                        <td>{{ $row->product_name }}</td>
                                        <td>{{ $row->hub_name }}</td>
                                        <td>{{ $row->stock ?? 0 }}</td>
                                        <td>{{ $row->fresh_stock ?? 0 }}</td>
                                        <td>{{ $row->variance ?? 0 }}</td>
                                        <td>
                                            <select class="form-control form-control-sm change_status"
                                                data-table="inventory" data-id="{{ $row->id }}">
                                                <option value="1" {{ ($row->status ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ ($row->status ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(method_exists($rows, 'links'))
                            {{ $rows->appends(request()->query())->links() }}
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
