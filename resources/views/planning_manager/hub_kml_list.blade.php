@extends('planning_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Hub KML List</h4>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Hub Name</th>
                            <th>City</th>
                            <th>KML File</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hubs as $i => $row)
                        <tr>
                            <td>{{ $hubs->firstItem() + $i }}</td>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->city_name ?? '-' }}</td>
                            <td>
                                @if(!empty($row->kml_file))
                                    <a href="{{ asset('uploads/kml/'.$row->kml_file) }}" target="_blank" class="btn btn-xs btn-info"><i class="fa fa-download"></i> Download</a>
                                @else
                                    <span class="text-muted">Not uploaded</span>
                                @endif
                            </td>
                            <td>{{ $row->latitude ?? '-' }}</td>
                            <td>{{ $row->longitude ?? '-' }}</td>
                            <td>
                                <select class="form-control form-control-sm change_status" data-table="hubs" data-id="{{ $row->id }}">
                                    <option value="1" {{ $row->status==1?'selected':'' }}>Active</option>
                                    <option value="0" {{ $row->status==0?'selected':'' }}>Inactive</option>
                                </select>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">No hubs found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $hubs->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
