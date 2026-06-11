@extends('admin.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="col-md-12">
          <div class="section-content">

            <div class="content-head">
              <h4 class="content-title">{{ $row ? 'Update Hub' : 'Add Hub' }}</h4>
            </div>

            <div class="content-details show">
              @if($errors->any())
                <div class="alert alert-danger">
                  <ul class="mb-0">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                  </ul>
                </div>
              @endif

              <div class="card pay-invoice">
                <div class="card-body">
                  <form method="post" action="{{ route('admin.hub_store') }}">
                    @csrf
                    @if($row)<input type="hidden" name="id" value="{{ $row->id }}">@endif

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Hub Name</label></div>
                      <div class="col-12 col-md-9">
                        <input type="text" name="hub" class="form-control" required
                               value="{{ old('hub', $row->hub ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>City</label></div>
                      <div class="col-12 col-md-9">
                        <select name="city_id" class="form-control" required>
                          <option value="">Select City</option>
                          @foreach($cities as $c)
                            <option value="{{ $c->id }}"
                              {{ (string) old('city_id', $row->city_id ?? '') === (string) $c->id ? 'selected' : '' }}>
                              {{ $c->city }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Type</label></div>
                      <div class="col-12 col-md-9">
                        <input type="text" name="type" class="form-control"
                               value="{{ old('type', $row->type ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Hub Latitude</label></div>
                      <div class="col-12 col-md-9">
                        <input type="text" name="hub_lat" class="form-control"
                               value="{{ old('hub_lat', $row->hub_lat ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Hub Longitude</label></div>
                      <div class="col-12 col-md-9">
                        <input type="text" name="hub_lng" class="form-control"
                               value="{{ old('hub_lng', $row->hub_lng ?? '') }}">
                      </div>
                    </div>

                    <div class="card-footer">
                      <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-dot-circle-o"></i> {{ $row ? 'Update' : 'Add' }}
                      </button>
                      <a href="{{ route('admin.hub') }}" class="btn btn-secondary btn-sm">Cancel</a>
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
