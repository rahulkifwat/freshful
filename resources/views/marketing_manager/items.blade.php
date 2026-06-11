@extends('marketing_manager.layout.master')
@section('content')
<div class="right-panel">
    <div class="content">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="page-title">Items / SKU</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('marketing_manager.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Items</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col"><h4 class="card-title mb-0">All Items</h4></div>
                        <div class="col-auto">
                            <form method="GET" action="{{ route('marketing_manager.items') }}" class="form-inline">
                                <input type="text" name="search" class="form-control form-control-sm mr-2"
                                       value="{{ $search }}" placeholder="Search items…">
                                <button class="btn btn-sm btn-primary">Search</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(method_exists($rows, 'total') && $rows->total() == 0)
                        <div class="alert alert-info">No items found.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>#</th><th>Item Name</th><th>Category</th><th>Status</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                @foreach($rows as $row)
                                    <tr>
                                        <td>{{ $row->id }}</td>
                                        <td>
                                            @if(!empty($row->image))
                                                <img src="{{ asset($row->image) }}" style="height:32px;border-radius:4px;margin-right:6px;">
                                            @endif
                                            {{ $row->item ?? '—' }}
                                        </td>
                                        <td>{{ $row->cat_name }}</td>
                                        <td>
                                            <select class="form-control form-control-sm change_status"
                                                data-table="items" data-id="{{ $row->id }}">
                                                <option value="1" {{ ($row->status ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ ($row->status ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-danger deleteRecord"
                                                data-table="items" data-id="{{ $row->id }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $rows->appends(request()->query())->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
