<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketing Manager — Login</title>
    <link rel="stylesheet" href="{{ url('assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/admin/css/style.css') }}">
    <style>
        body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { width: 100%; max-width: 420px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,.12); }
        .login-header { background: #ec3f34; color: #fff; padding: 28px 32px 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .login-header h3 { margin: 0; font-size: 1.4rem; }
        .login-body { background: #fff; padding: 28px 32px 32px; border-radius: 0 0 8px 8px; }
        .btn-login { background: #ec3f34; border: none; width: 100%; padding: 10px; color: #fff; border-radius: 4px; font-size: 1rem; }
        .btn-login:hover { background: #c9302c; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <img src="{{ asset('assets/image/logo.png') }}" alt="Logo" style="height:40px;margin-bottom:10px;"><br>
        <h3>Marketing Manager</h3>
    </div>
    <div class="login-body">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <form method="POST" action="{{ route('marketing_manager.login.submit') }}">
            @csrf
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" placeholder="Enter email" required autofocus>
                @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       placeholder="Enter password" required>
                @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn-login mt-2">Login</button>
        </form>
    </div>
</div>
</body>
</html>
