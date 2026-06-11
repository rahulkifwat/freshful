@extends('admin.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="contents-inner">
          <div class="row">
            <div class="col-md-12">
              <div class="section-content">

                <div class="content-head">
                  <h4 class="content-title">Edit Profile</h4>
                </div>

                <div class="content-details show">
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

                  <div class="card pay-invoice">
                    <div class="card-body">
                      <form method="post" action="{{ route('admin.update_profile') }}">
                        @csrf

                        <div class="row form-group">
                          <div class="col col-md-3"><label>Name</label></div>
                          <div class="col-12 col-md-9">
                            <input type="text" name="name" class="form-control" required
                                   value="{{ old('name', $admin->name) }}">
                          </div>
                        </div>

                        <div class="row form-group">
                          <div class="col col-md-3"><label>Email</label></div>
                          <div class="col-12 col-md-9">
                            <input type="email" name="email" class="form-control" required
                                   value="{{ old('email', $admin->email) }}">
                          </div>
                        </div>

                        <div class="row form-group">
                          <div class="col col-md-3"><label>Phone</label></div>
                          <div class="col-12 col-md-9">
                            <input type="text" name="phone" class="form-control" maxlength="20"
                                   value="{{ old('phone', $admin->phone ?? '') }}">
                          </div>
                        </div>

                        <hr>
                        <p class="text-muted">Leave password fields blank to keep your current password.</p>

                        <div class="row form-group">
                          <div class="col col-md-3"><label>New Password</label></div>
                          <div class="col-12 col-md-9">
                            <input type="password" name="password" class="form-control" minlength="6">
                          </div>
                        </div>

                        <div class="row form-group">
                          <div class="col col-md-3"><label>Confirm Password</label></div>
                          <div class="col-12 col-md-9">
                            <input type="password" name="password_confirmation" class="form-control" minlength="6">
                          </div>
                        </div>

                        <div class="card-footer">
                          <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa fa-dot-circle-o"></i> Save Changes
                          </button>
                          <a href="{{ route('admin.profile') }}" class="btn btn-secondary btn-sm">Cancel</a>
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
  </div>
</div>
@endsection
