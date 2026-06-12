<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operation Manager Login | {{ config('app.name','Freshful') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/image/Fevi.png') }}" sizes="32x32">
    <link rel="stylesheet" href="{{ url('assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/admin/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/admin/css/style.css') }}">
    <style>
        body { background: #f5f5f5; display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .login-card { background:#fff; border-radius:8px; box-shadow:0 2px 16px rgba(0,0,0,.12); max-width:420px; width:100%; }
        .login-header { background:#28a745; color:#fff; border-radius:8px 8px 0 0; padding:28px 32px 20px; text-align:center; }
        .login-header h4 { margin:0; font-size:22px; }
        .login-body { padding:28px 32px; }
        .btn-login { background:#28a745; border:none; color:#fff; width:100%; padding:10px; font-size:16px; border-radius:5px; }
        .btn-login:hover { background:#218838; color:#fff; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <img src="{{ asset('assets/image/logo.png') }}" alt="Logo" style="height:48px;margin-bottom:10px;"><br>
        <h4>Operation Manager</h4>
    </div>
    <div class="login-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <form method="POST" action="{{ route('operation_manager.login_submit') }}">
            @csrf
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="Enter email">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Enter password">
            </div>
            <button type="submit" class="btn btn-login mt-2">Login</button>
        </form>
    </div>
</div>
</body>
</html>
