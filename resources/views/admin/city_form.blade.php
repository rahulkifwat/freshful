@extends('admin.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="col-md-12">
          <div class="section-content">

            <div class="content-head">
              <h4 class="content-title">{{ $row ? 'Update City' : 'Add City' }}</h4>
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
                  <form method="post" action="{{ route('admin.city_store') }}">
                    @csrf
                    @if($row)<input type="hidden" name="id" value="{{ $row->id }}">@endif

                    <div class="row form-group">
                      <div class="col col-md-3"><label>City Name</label></div>
                      <div class="col-12 col-md-9">
                        <input type="text" name="city" class="form-control" required
                               value="{{ old('city', $row->city ?? '') }}">
                      </div>
                    </div>

                    @if($states->count())
                      <div class="row form-group">
                        <div class="col col-md-3"><label>State</label></div>
                        <div class="col-12 col-md-9">
                          <select name="state_id" class="form-control">
                            <option value="">Select State</option>
                            @foreach($states as $s)
                              <option value="{{ $s->id }}"
                                {{ (string) old('state_id', $row->state_id ?? '') === (string) $s->id ? 'selected' : '' }}>
                                {{ $s->name }}
                              </option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                    @endif

                    <div class="card-footer">
                      <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-dot-circle-o"></i> {{ $row ? 'Update' : 'Add' }}
                      </button>
                      <a href="{{ route('admin.city') }}" class="btn btn-secondary btn-sm">Cancel</a>
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
