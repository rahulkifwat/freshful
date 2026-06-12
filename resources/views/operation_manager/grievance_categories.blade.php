@extends('operation_manager.layout.master')
@section('content')
<div class="right-panel">
    <div class="content">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="page-title">Grievance Categories</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('operation_manager.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Grievance Categories</li>
                        </ul>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">Add / Edit Category</h4></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('operation_manager.grievance_category.store') }}" id="categoryForm">
                                @csrf
                                <input type="hidden" name="id" id="catId">
                                <div class="form-group">
                                    <label>Category Name</label>
                                    <input type="text" name="category" id="catName" class="form-control" placeholder="Enter category" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Save Category</button>
                                <button type="button" class="btn btn-secondary btn-block mt-1" onclick="resetForm()">Clear</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">All Categories</h4></div>
                        <div class="card-body">
                            @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty() || (method_exists($rows, 'total') && $rows->total() == 0))
                                <div class="alert alert-info">No categories found.</div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead><tr><th>#</th><th>Category</th><th>Action</th></tr></thead>
                                        <tbody>
                                        @foreach($rows as $row)
                                            <tr>
                                                <td>{{ $row->id }}</td>
                                                <td>{{ $row->category }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning"
                                                        onclick="editCat({{ $row->id }}, '{{ addslashes($row->category) }}')">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger deleteRecord"
                                                        data-table="grievance_category" data-id="{{ $row->id }}">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if(method_exists($rows, 'links'))
                                    {{ $rows->links() }}
                                @endif
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
function editCat(id, name) {
    document.getElementById('catId').value = id;
    document.getElementById('catName').value = name;
}
function resetForm() {
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
}
</script>
@endpush
@endsection
