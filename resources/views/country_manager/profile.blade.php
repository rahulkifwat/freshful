@extends('country_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head">
          <h4 class="content-title">My Profile</h4>
          <a href="{{ route('country_manager.edit_profile') }}" class="btn btn-primary btn-sm">Edit Profile</a>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <div class="content-details show">
          <div class="card" style="max-width:500px">
            <div class="card-body">
              <table class="table table-bordered mb-0">
                <tr><th style="width:140px">Name</th><td>{{ $user->name ?? '—' }}</td></tr>
                <tr><th>Email</th><td>{{ $user->email ?? '—' }}</td></tr>
                <tr><th>Phone</th><td>{{ $user->phone ?? '—' }}</td></tr>
                <tr><th>Address</th><td>{{ $user->address ?? '—' }}</td></tr>
                <tr><th>Status</th><td>
                  <span class="badge badge-{{ ($user->status ?? 'active') === 'active' ? 'success' : 'secondary' }}">
                    {{ $user->status ?? 'active' }}
                  </span>
                </td></tr>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div></div></div></div></div>
  </div>
</div>
@endsection
