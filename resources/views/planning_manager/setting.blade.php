@extends('planning_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Settings</h4>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header"><b>Change Password</b></div>
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif
                        <form method="POST" action="{{ route('planning_manager.change_password') }}">
                            @csrf
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" class="form-control" required placeholder="Current password">
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" class="form-control" required placeholder="New password (min 6 chars)">
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm new password">
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
