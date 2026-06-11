@extends('admin.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="col-md-12">
          <div class="section-content">

            <div class="content-head">
              <h4 class="content-title">Privacy Policy</h4>
            </div>

            <div class="content-details show">
              @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
              @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
              @endif

              <div class="card pay-invoice">
                <div class="card-body">
                  <form method="post" action="{{ route('admin.privacy_policy_update') }}">
                    @csrf
                    <div class="form-group">
                      <label>Policy content</label>
                      <textarea name="content" class="form-control" rows="20" required>{{ old('content', $row->content ?? '') }}</textarea>
                      <small class="text-muted">HTML allowed.</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                      <i class="fa fa-dot-circle-o"></i> Save
                    </button>
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
