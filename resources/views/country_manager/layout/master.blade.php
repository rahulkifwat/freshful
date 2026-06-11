<!DOCTYPE html>
<html lang="en">
@include('country_manager.layout.head')

<body class="index-page index-01">
    @include('country_manager.layout.header')

    <div class="content-wrapper container-fluid">
        @include('country_manager.layout.sidebar')
        @yield('content')
    </div>

    @include('country_manager.layout.footer')
    @stack('scripts')
</body>

</html>
