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
                  <h4 class="content-title">My Profile</h4>
                  <a href="{{ route('admin.edit_profile') }}" class="btn btn-primary btn-sm" style="float:right;">
                    <i class="fa fa-pencil"></i> Edit Profile
                  </a>
                </div>

                <div class="content-details show">

                  @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                  @endif

                  <div class="card">
                    <div class="card-body">
                      <p><strong>Name:</strong> {{ $admin->name }}</p>
                      <p><strong>Email:</strong> {{ $admin->email }}</p>
                      <p><strong>Phone:</strong> {{ $admin->phone ?? '-' }}</p>
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
