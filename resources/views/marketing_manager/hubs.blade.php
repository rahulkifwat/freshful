@extends('marketing_manager.layout.master')
@section('content')
<div class="right-panel">
    <div class="content">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="page-title">Hubs</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('marketing_manager.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Hubs</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">All Hubs</h4></div>
                <div class="card-body">
                    @if(method_exists($rows, 'total') && $rows->total() == 0)
                        <div class="alert alert-info">No hubs found.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr><th>#</th><th>Hub Name</th><th>City</th><th>Status</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                @foreach($rows as $row)
                                    <tr>
                                        <td>{{ $row->id }}</td>
                                        <td>{{ $row->hub }}</td>
                                        <td>{{ $row->city_name }}</td>
                                        <td>
                                            <select class="form-control form-control-sm change_status"
                                                data-table="hubs" data-id="{{ $row->id }}">
                                                <option value="1" {{ ($row->status ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ ($row->status ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-danger deleteRecord"
                                                data-table="hubs" data-id="{{ $row->id }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $rows->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
