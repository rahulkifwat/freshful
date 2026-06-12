<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS | {{ config('app.name','Freshful') }}</title>
    <link rel="shortcut icon" href="{{ asset('assets/image/Fevi.png') }}">
    <link rel="stylesheet" href="{{ url('assets/pos/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/pos/css/icons.min.css') }}">
    <link rel="stylesheet" href="{{ url('assets/pos/css/app.min.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    @stack('styles')
</head>
<body>
<div id="wrapper">
    @include('pos.layout.header')
    @include('pos.layout.sidebar')
    @yield('content')
    @include('pos.layout.footer')
</div>
@include('pos.layout.scripts')
@stack('scripts')
</body>
</html>
