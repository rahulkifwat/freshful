@extends('planning_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="page-title">Upload Inventory</h4>
                <a href="{{ asset('planning_manager/inventory_upload.xlsx') }}" class="btn btn-primary btn-sm"><i class="fa fa-download"></i> Download Excel Format</a>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header"><b>Upload Inventory File</b></div>
            <div class="card-body">
                <form method="POST" action="{{ route('planning_manager.upload_inventory') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-4 form-group">
                            <label>Hub</label>
                            <select name="hub_id" class="form-control" required>
                                <option value="">Select Hub</option>
                                @foreach($hubs as $h)
                                <option value="{{ $h->id }}" {{ $hub_id==$h->id?'selected':'' }}>{{ $h->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Excel File (.xlsx)</label>
                            <input type="file" name="inventory_file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <button type="submit" class="btn btn-success"><i class="fa fa-upload"></i> Upload</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <b>Current Inventory</b>
                <form method="GET" class="d-flex align-items-center">
                    <select name="hub_id" class="form-control form-control-sm mr-2">
                        <option value="">All Hubs</option>
                        @foreach($hubs as $h)
                        <option value="{{ $h->id }}" {{ $hub_id==$h->id?'selected':'' }}>{{ $h->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-filter"></i> Filter</button>
                </form>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                        <tr>
                            <td>{{ $rows->firstItem() + $i }}</td>
                            <td>
                                @if(!empty($row->image))
                                    <img src="{{ asset('uploads/items/'.$row->image) }}" height="40" alt="">
                                @else <span class="text-muted">-</span> @endif
                            </td>
                            <td>{{ $row->title ?? '-' }}</td>
                            <td>{{ $row->cat_name ?? '-' }}</td>
                            <td>{{ $row->inventory_stock ?? 0 }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">No inventory records found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $rows->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
