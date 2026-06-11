@extends('marketing_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">Edit Profile</h4></div>
        <div class="content-details show">
          @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
          @endif
          <div class="card" style="max-width:500px">
            <div class="card-body">
              <form method="POST" action="{{ route('marketing_manager.update_profile') }}">
                @csrf
                <div class="form-group">
                  <label>Name</label>
                  <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="form-group">
                  <label>Address</label>
                  <textarea name="address" class="form-control" rows="3">{{ old('address', $user->address ?? '') }}</textarea>
                </div>
                <div class="form-group">
                  <label>New Password <small class="text-muted">(leave blank to keep current)</small></label>
                  <input type="password" name="password" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
                <a href="{{ route('marketing_manager.profile') }}" class="btn btn-secondary ml-2">Cancel</a>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div></div></div></div></div>
  </div>
</div>
@endsection
