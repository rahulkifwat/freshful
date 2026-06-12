@extends('production.layout.master')
@section('content')
<div class="dashboard-contents">
    <div class="contents-inner">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="section-content">
                    <div class="content-head">
                        <h4 class="content-title">Change Password</h4>
                    </div>
                    <div class="content-details show">
                        <form method="POST" action="{{ route('production.change_password') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Current Password</label>
                                        <input type="password" name="current_password" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" name="new_password" class="form-control" required minlength="6">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
