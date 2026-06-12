@extends('production.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Certificates</h4>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-header"><b>Add / Edit Certificate</b></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('production.certificate') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="cert_id" id="cert_id" value="">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" id="cert_title" class="form-control" required placeholder="Certificate title">
                            </div>
                            <div class="form-group">
                                <label>Image</label>
                                <input type="file" name="image" class="form-control-file" accept="image/*">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
                            <button type="button" class="btn btn-secondary" onclick="resetCertForm()"><i class="fa fa-refresh"></i> Reset</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header"><b>Certificate List</b></div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr><th>#</th><th>Title</th><th>Image</th><th>Status</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                @forelse($certificates as $i => $row)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ $row->title }}</td>
                                    <td>
                                        @if(!empty($row->image))
                                            <img src="{{ asset('uploads/certificates/'.$row->image) }}" height="40" alt="">
                                        @else <span class="text-muted">-</span> @endif
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm change_status" data-table="certificates" data-id="{{ $row->id }}">
                                            <option value="1" {{ $row->status==1?'selected':'' }}>Active</option>
                                            <option value="0" {{ $row->status==0?'selected':'' }}>Inactive</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning editCert" data-id="{{ $row->id }}" data-title="{{ $row->title }}"><i class="fa fa-edit"></i></button>
                                        <button class="btn btn-sm btn-danger deleteRecord" data-table="certificates" data-id="{{ $row->id }}"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center">No certificates found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function resetCertForm(){ document.getElementById('cert_id').value=''; document.getElementById('cert_title').value=''; }
$(document).on('click','.editCert',function(){
    document.getElementById('cert_id').value=$(this).data('id');
    document.getElementById('cert_title').value=$(this).data('title');
    window.scrollTo(0,0);
});
</script>
@endpush
@endsection
