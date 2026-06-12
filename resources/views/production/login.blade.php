<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Production Login | {{ config('app.name','Freshful') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/image/Fevi.png') }}" sizes="32x32">
    <link rel="stylesheet" href="{{ url('assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/admin/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ url('assets/admin/css/pages/pages.css') }}">
    <style>
        .btn-primary { background-color:#6f42c1!important; border-color:#6f42c1!important; }
        .login-content { background:rgba(0,0,0,.6); border-radius:8px; }
        .login-logo h3 { color:#fff; }
    </style>
</head>
<body class="authentication-bg">
    <div class="admin-login d-flex align-content-center flex-wrap"
         style="background:url('{{ asset('assets/admin/images/login-bg.jpg') }}') center/cover no-repeat; min-height:100vh;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="login-content p-4 mt-5">
                        <div class="login-logo text-center pb-3">
                            <a href="{{ route('production.login') }}">
                                <img src="{{ asset('assets/image/logo.png') }}" alt="Logo" height="55">
                            </a>
                            <h3 class="mt-2">Production Manager</h3>
                        </div>
                        @if(session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                        <div class="login-form">
                            <form method="POST" action="{{ route('production.login_submit') }}" autocomplete="off">
                                @csrf
                                <div class="form-group">
                                    <input type="email" name="email" class="form-control" placeholder="Email"
                                           value="{{ old('email') }}" required autofocus>
                                    @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block mt-3">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ url('assets/admin/js/jquery-3.2.1.slim.min.js') }}"></script>
    <script src="{{ url('assets/admin/js/plugins.js') }}"></script>
    <script src="{{ url('assets/admin/js/main.js') }}"></script>
</body>
</html>
