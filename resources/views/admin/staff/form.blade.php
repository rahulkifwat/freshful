@extends('admin.layout.master')

@section('content')
@php
  $editing = (bool) $row;
  $action  = $editing
    ? url('admin/staff/'.$cfg['key'].'/update')
    : url('admin/staff/'.$cfg['key'].'/store');
@endphp

<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="col-md-12">
          <div class="section-content">
            <div class="content-head">
              <h4 class="content-title">{{ $editing ? 'Edit' : 'Add' }} {{ $cfg['label'] }}</h4>
            </div>

            <div class="content-details show">
              <div class="card pay-invoice">
                <div class="card-body">

                  @if($errors->any())
                    <div class="alert alert-danger">
                      <ul class="mb-0">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                      </ul>
                    </div>
                  @endif
                  @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                  @endif
                  @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                  @endif

                  <form method="post" action="{{ $action }}">
                    @csrf
                    @if($editing)
                      <input type="hidden" name="id" value="{{ $row->id }}">
                    @endif

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Name</label></div>
                      <div class="col-12 col-md-9">
                        <input type="text" name="name" class="form-control" required
                               value="{{ old('name', $row->name ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Email</label></div>
                      <div class="col-12 col-md-9">
                        <input type="email" name="email" class="form-control" required
                               value="{{ old('email', $row->email ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Phone</label></div>
                      <div class="col-12 col-md-9">
                        <input type="text" name="phone" class="form-control" maxlength="10"
                               value="{{ old('phone', $row->phone ?? '') }}">
                      </div>
                    </div>

                    @if($cfg['table'] === 'hub_user' && $hubs->count())
                      <div class="row form-group">
                        <div class="col col-md-3"><label>HUB</label></div>
                        <div class="col-12 col-md-9">
                          <select name="hub_id" class="form-control">
                            <option value="">Select HUB</option>
                            @foreach($hubs as $h)
                              <option value="{{ $h->id }}"
                                {{ (string) old('hub_id', $row->hub_id ?? '') === (string) $h->id ? 'selected' : '' }}>
                                {{ $h->hub }}
                              </option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                    @endif

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Password</label></div>
                      <div class="col-12 col-md-9">
                        <input type="password" name="password" class="form-control"
                               placeholder="{{ $editing ? 'Leave blank to keep current password' : '' }}"
                               {{ $editing ? '' : 'required' }}>
                      </div>
                    </div>

                    <div class="card-footer">
                      <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-dot-circle-o"></i> {{ $editing ? 'Save Changes' : 'Add' }}
                      </button>
                      <a href="{{ url('admin/'.$cfg['list_url']) }}" class="btn btn-secondary btn-sm">Cancel</a>
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
