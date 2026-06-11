@extends('admin.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="contents-inner">
          <div class="row">
            <div class="col-12">
              <div class="section-content">

                <div class="content-head">
                  <h4 class="content-title">Certificates</h4>
                  <a href="{{ route('admin.certificate_form') }}" class="btn btn-primary btn-sm" style="float:right;">
                    <i class="fa fa-dot-circle-o"></i> Add Certificate
                  </a>
                </div>

                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

                <div class="content-details show">
                  <div class="table-responsive">
                    <table id="data-table" class="table data-table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Title</th>
                          <th>Image</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($rows as $r)
                          <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->title }}</td>
                            <td>
                              @if(!empty($r->image))
                                <img src="{{ asset('uploads/images/certificates/'.$r->image) }}" height="60" alt="">
                              @else - @endif
                            </td>
                            <td>
                              <a href="{{ route('admin.certificate_form', ['id' => $r->id]) }}" class="btn btn-info btn-sm">
                                <i class="fa fa-pencil-square-o"></i>
                              </a>
                              <button type="button" class="btn btn-danger btn-sm deleteRecord"
                                      data-table="certificates" data-id="{{ $r->id }}">
                                <i class="fa fa-trash"></i>
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="4" class="text-center">No certificates yet.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>
                  <div class="mt-3">{{ $rows->links() }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
