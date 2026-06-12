<!DOCTYPE html>
<html lang="en">
@include('planning_manager.layout.head')
<body class="index-01">
@include('planning_manager.layout.header')
<div class="content-wrapper container-fluid">
    @include('planning_manager.layout.sidebar')
    @yield('content')
</div>
@include('planning_manager.layout.footer')
</body>
</html>
