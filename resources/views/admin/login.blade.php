<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="{{ url('assets/admin/css/bootstrap.min.css') }}">

    <!-- Icons -->
    <link rel="stylesheet" href="{{ url('assets/admin/css/font-awesome.min.css') }}">

    <!-- Custom Style -->
    <link rel="stylesheet" href="{{ url('assets/admin/css/style.css') }}">

    <style>
        body {
            background: #eceff1;
            font-family: 'Open Sans', sans-serif;
        }

        .login-wrapper {
            min-height: 100vh;
            background: url('{{ asset('uploads/images/eat-2378726_960_720.jpg') }}') center/cover no-repeat;
            display: flex;
            align-items: center;
        }

        .login-box {
            background: rgba(0,0,0,0.6);
            padding: 30px;
            border-radius: 8px;
            color: #fff;
        }

        .form-control {
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

<div class="login-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-4">
                <div class="login-box">
                    <h3 class="text-center mb-4">Admin Login</h3>

                    <form method="POST" action="{{ route('admin.loginSubmit') }}" >
                        @csrf

                        <input type="email" name="email" class="form-control" placeholder="Email" required>

                        <input type="password" name="password" class="form-control" placeholder="Password" required>

                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            Login
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Required JS only -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="{{ url('assets/admin/js/bootstrap.min.js') }}"></script>

</body>
</html>