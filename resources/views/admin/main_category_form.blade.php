@extends('admin.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="col-md-12">
          <div class="section-content">

            <div class="content-head">
              <h4 class="content-title">{{ $row ? 'Edit Main Category' : 'Add Main Category' }}</h4>
              <a href="{{ route('admin.main_categories') }}" class="btn btn-secondary btn-sm" style="float:right;">
                <i class="fa fa-arrow-left"></i> Back
              </a>
            </div>

            <div class="content-details show">
              @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
              @endif

              <div class="card pay-invoice">
                <div class="card-body">
                  <form method="post" action="{{ route('admin.main_category_store') }}" enctype="multipart/form-data">
                    @csrf
                    @if($row)<input type="hidden" name="id" value="{{ $row->id }}">@endif

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Name <span class="text-danger">*</span></label></div>
                      <div class="col-12 col-md-6">
                        <input type="text" name="name" class="form-control" required
                               value="{{ old('name', $row->name ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Image</label></div>
                      <div class="col-12 col-md-6">
                        <input type="file" name="image" class="form-control" accept="image/*">
                        @if($row && !empty($row->image))
                          <div class="mt-2">
                            <img src="{{ asset('uploads/images/categories/'.$row->image) }}" height="60" alt="">
                            <small class="text-muted d-block">Leave empty to keep current image.</small>
                          </div>
                        @endif
                      </div>
                    </div>

                    <div class="card-footer">
                      <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-dot-circle-o"></i> {{ $row ? 'Update' : 'Add' }} Category
                      </button>
                      <a href="{{ route('admin.main_categories') }}" class="btn btn-secondary btn-sm">Cancel</a>
                    </div>
                  </form>
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
