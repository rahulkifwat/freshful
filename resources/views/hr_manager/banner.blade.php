@extends('hr_manager.layout.master')
@section('content')
<div class="right-panel">
    <div class="content">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="page-title">Banners</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('hr_manager.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Banners</li>
                        </ul>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="card-header"><h4 class="card-title">All Banners</h4></div>
                <div class="card-body">
                    @if(($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty()) || (method_exists($rows, 'total') && $rows->total() == 0))
                        <div class="alert alert-info">No banners found.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        @foreach(array_keys((array) $rows->first()) as $col)
                                            <th>{{ ucwords(str_replace('_', ' ', $col)) }}</th>
                                        @endforeach
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($rows as $row)
                                    <tr>
                                        @foreach((array) $row as $key => $val)
                                            <td>
                                                @if(in_array($key, ['image', 'banner_image', 'img']))
                                                    @if($val)
                                                        <img src="{{ asset($val) }}" style="height:50px;border-radius:4px;">
                                                    @else
                                                        —
                                                    @endif
                                                @elseif($key === 'status')
                                                    <select class="form-control form-control-sm change_status"
                                                        data-table="banner" data-id="{{ $row->id }}">
                                                        <option value="1" {{ $val == 1 ? 'selected' : '' }}>Active</option>
                                                        <option value="0" {{ $val == 0 ? 'selected' : '' }}>Inactive</option>
                                                    </select>
                                                @else
                                                    {{ $val ?? '—' }}
                                                @endif
                                            </td>
                                        @endforeach
                                        <td>
                                            <button class="btn btn-sm btn-danger deleteRecord"
                                                data-table="banner" data-id="{{ $row->id }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
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
