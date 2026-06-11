<!DOCTYPE html>
<html lang="en">
@include('marketing_manager.layout.head')

<body class="index-page index-01">
    @include('marketing_manager.layout.header')

    <div class="content-wrapper container-fluid">
        @include('marketing_manager.layout.sidebar')
        @yield('content')
    </div>

    @include('marketing_manager.layout.footer')
    @stack('scripts')
</body>

</html>
