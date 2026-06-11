@extends('admin.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="col-md-12">
          <div class="section-content">

            <div class="content-head">
              <h4 class="content-title">{{ $row ? 'Update Banner' : 'Add Banner' }}</h4>
            </div>

            <div class="content-details show">
              @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
              @endif

              <div class="card pay-invoice">
                <div class="card-body">
                  <form method="post" action="{{ route('admin.banner_store') }}" enctype="multipart/form-data">
                    @csrf
                    @if($row)<input type="hidden" name="id" value="{{ $row->id }}">@endif

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Title</label></div>
                      <div class="col-12 col-md-9">
                        <input type="text" name="title" class="form-control" required value="{{ old('title', $row->title ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>URL</label></div>
                      <div class="col-12 col-md-9">
                        <input type="text" name="url" class="form-control" placeholder="https://..." value="{{ old('url', $row->url ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Image</label></div>
                      <div class="col-12 col-md-9">
                        <input type="file" name="image" class="form-control" accept="image/*">
                        @if($row && !empty($row->image))
                          <div class="mt-2">
                            <img src="{{ asset('uploads/images/banners/'.$row->image) }}" height="80" alt="" />
                            <small class="text-muted d-block">Leave empty to keep current image.</small>
                          </div>
                        @endif
                      </div>
                    </div>

                    <div class="card-footer">
                      <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-dot-circle-o"></i> {{ $row ? 'Update' : 'Add' }}
                      </button>
                      <a href="{{ route('admin.banners') }}" class="btn btn-secondary btn-sm">Cancel</a>
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
