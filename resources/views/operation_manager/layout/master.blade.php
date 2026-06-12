<!DOCTYPE html>
<html lang="en">
@include('operation_manager.layout.head')
<body class="index-01">
@include('operation_manager.layout.header')
<div class="content-wrapper container-fluid">
    @include('operation_manager.layout.sidebar')
    @yield('content')
</div>
@include('operation_manager.layout.footer')
</body>
</html>
