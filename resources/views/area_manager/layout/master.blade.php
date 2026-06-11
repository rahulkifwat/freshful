<!DOCTYPE html>
<html lang="en">
@include('area_manager.layout.head')

<body class="index-page index-01">
    @include('area_manager.layout.header')

    <div class="content-wrapper container-fluid">
        @include('area_manager.layout.sidebar')
        @yield('content')
    </div>

    @include('area_manager.layout.footer')
    @stack('scripts')
</body>

</html>
