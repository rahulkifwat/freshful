@extends('marketing_manager.layout.master')
@section('content')
<div class="right-panel">
    <div class="content">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="page-title">Product Categories</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('marketing_manager.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Categories</li>
                        </ul>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">Add / Edit Category</h4></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('marketing_manager.category.store') }}" id="catForm">
                                @csrf
                                <input type="hidden" name="id" id="catId">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" name="name" id="catName" class="form-control" placeholder="Category name" required>
                                </div>
                                <div class="form-group">
                                    <label>Group Type</label>
                                    <input type="text" name="group_type" id="catGroup" class="form-control" placeholder="e.g. veg, fruit">
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Save</button>
                                <button type="button" class="btn btn-secondary btn-block mt-1" onclick="resetCatForm()">Clear</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">All Categories</h4></div>
                        <div class="card-body">
                            @if(method_exists($rows, 'total') && $rows->total() == 0)
                                <div class="alert alert-info">No categories found.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead><tr><th>#</th><th>Name</th><th>Group Type</th><th>Status</th><th>Action</th></tr></thead>
                                        <tbody>
                                        @foreach($rows as $row)
                                            <tr>
                                                <td>{{ $row->id }}</td>
                                                <td>{{ $row->name }}</td>
                                                <td>{{ $row->group_type ?? '—' }}</td>
                                                <td>
                                                    <select class="form-control form-control-sm change_status"
                                                        data-table="category" data-id="{{ $row->id }}">
                                                        <option value="1" {{ ($row->status ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                                                        <option value="0" {{ ($row->status ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning"
                                                        onclick="editCat({{ $row->id }}, '{{ addslashes($row->name) }}', '{{ addslashes($row->group_type ?? '') }}')">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger deleteRecord"
                                                        data-table="category" data-id="{{ $row->id }}">
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
    </div>
</div>
@push('scripts')
<script>
function editCat(id, name, group) {
    document.getElementById('catId').value = id;
    document.getElementById('catName').value = name;
    document.getElementById('catGroup').value = group;
}
function resetCatForm() {
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catGroup').value = '';
}
</script>
@endpush
@endsection
