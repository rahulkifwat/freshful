<!DOCTYPE html>
<html lang="en">
@include('production.layout.head')
<body class="index-01">
@include('production.layout.header')
<div class="content-wrapper container-fluid">
    @include('production.layout.sidebar')
    @yield('content')
</div>
@include('production.layout.footer')
</body>
</html>
