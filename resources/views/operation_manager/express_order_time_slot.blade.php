@extends('operation_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Express Order Time Slots</h4>
            </div>
        </div>
        <div class="card shadow mb-4">
            <div class="card-header"><b>Add Time Slot</b></div>
            <div class="card-body">
                <form method="POST" action="{{ route('operation_manager.express_order_time_slot') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Max Orders</label>
                            <input type="number" name="max_orders" class="form-control" min="1" placeholder="Max orders per slot">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add Slot</button>
                </form>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-header"><b>Existing Time Slots</b></div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr><th>#</th><th>Start Time</th><th>End Time</th><th>Max Orders</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse($slots as $i => $row)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>{{ $row->start_time }}</td>
                            <td>{{ $row->end_time }}</td>
                            <td>{{ $row->max_orders ?? '∞' }}</td>
                            <td>
                                <select class="form-control form-control-sm change_status" data-table="express_time_slots" data-id="{{ $row->id }}">
                                    <option value="1" {{ $row->status==1?'selected':'' }}>Active</option>
                                    <option value="0" {{ $row->status==0?'selected':'' }}>Inactive</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger deleteRecord" data-table="express_time_slots" data-id="{{ $row->id }}"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">No slots found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
