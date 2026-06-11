@extends('admin.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="col-md-12">
          <div class="section-content">

            <div class="content-head">
              <h4 class="content-title">{{ $row ? 'Update Promotion' : 'Add Promotion' }}</h4>
            </div>

            <div class="content-details show">
              @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
              @endif

              <div class="card pay-invoice">
                <div class="card-body">
                  <form method="post" action="{{ route('admin.promotion_store') }}" enctype="multipart/form-data">
                    @csrf
                    @if($row)<input type="hidden" name="id" value="{{ $row->id }}">@endif

                    <div class="row form-group">
                      <div class="col col-md-3"><label>User Eligibility</label></div>
                      <div class="col-12 col-md-9">
                        @php $ue = old('user_eligible', $row->user_eligible ?? 'everyone'); @endphp
                        <select name="user_eligible" class="form-control">
                          <option value="everyone" {{ $ue === 'everyone' ? 'selected' : '' }}>Everyone</option>
                          <option value="new user" {{ $ue === 'new user' ? 'selected' : '' }}>New User</option>
                        </select>
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Promocode <span class="text-danger">*</span></label></div>
                      <div class="col-12 col-md-9">
                        <input type="text" name="title" class="form-control" required value="{{ old('title', $row->title ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Image</label></div>
                      <div class="col-12 col-md-9">
                        <input type="file" name="image" class="form-control" accept="image/*">
                        @if($row && !empty($row->image))
                          <div class="mt-2">
                            <img src="{{ asset('uploads/images/promotions/'.$row->image) }}" height="60" alt="" />
                            <small class="text-muted d-block">Leave empty to keep current image.</small>
                          </div>
                        @endif
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Percentage % <span class="text-danger">*</span></label></div>
                      <div class="col-12 col-md-9">
                        <input type="number" min="0" step="0.01" name="percentage" class="form-control" required
                               value="{{ old('percentage', $row->percentage ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Min Amount <span class="text-danger">*</span></label></div>
                      <div class="col-12 col-md-9">
                        <input type="number" min="0" step="0.01" name="min_amount" class="form-control" required
                               value="{{ old('min_amount', $row->min_amount ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Description</label></div>
                      <div class="col-12 col-md-9">
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $row->description ?? '') }}</textarea>
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Expiry</label></div>
                      <div class="col-12 col-md-9">
                        @php $exp = old('expiry', $row->expiry ?? ''); $exp = ($exp === '0' || $exp === 0) ? '' : $exp; @endphp
                        <input type="date" name="expiry" class="form-control" min="{{ date('Y-m-d') }}" value="{{ $exp }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Max Usage</label></div>
                      <div class="col-12 col-md-9">
                        <input type="number" min="0" name="max_usage" class="form-control" placeholder="0 = unlimited"
                               value="{{ old('max_usage', $row->max_usage ?? '') }}">
                      </div>
                    </div>

                    <div class="card-footer">
                      <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-dot-circle-o"></i> {{ $row ? 'Update' : 'Add' }}
                      </button>
                      <a href="{{ route('admin.promotions') }}" class="btn btn-secondary btn-sm">Cancel</a>
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
