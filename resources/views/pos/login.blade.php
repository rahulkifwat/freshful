<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS Login | {{ config('app.name','Freshful') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/image/Fevi.png') }}">
    <link rel="stylesheet" href="{{ url('assets/pos/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/pos/css/icons.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/pos/css/app.min.css') }}">
    <style>
        .btn-primary { background:#e60000!important; border:1px solid #e60000!important; }
        .card.bg-pattern { background:rgba(255,255,255,.2); }
    </style>
</head>
<body class="authentication-bg authentication-bg-pattern">
    <div class="account-pages mt-5 mb-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card bg-pattern">
                        <div class="card-body p-4">
                            <div class="text-center w-75 m-auto">
                                <a href="{{ route('pos.login') }}">
                                    <img src="{{ asset('assets/image/logo.png') }}" alt="" height="60">
                                </a>
                                <p class="text-white-50 mb-4 mt-2">POS — Point of Sale</p>
                            </div>
                            @if(session('error'))
                                <div class="alert alert-danger">{{ session('error') }}</div>
                            @endif
                            <form method="POST" action="{{ route('pos.login_submit') }}" autocomplete="off">
                                @csrf
                                <div class="form-group mb-3">
                                    <label class="text-white">Email address</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="Email" required autofocus>
                                    @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group mb-3">
                                    <label class="text-white">Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                                </div>
                                <div class="form-group mb-0 text-center">
                                    <button class="btn btn-primary btn-block" type="submit">Log In</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ url('assets/pos/js/vendor.min.js') }}"></script>
    <script src="{{ url('assets/pos/js/app.min.js') }}"></script>
</body>
</html>
