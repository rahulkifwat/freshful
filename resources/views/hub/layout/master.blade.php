<!DOCTYPE html>
<html lang="en">
@include('hub.layout.head')

<body class="index-page index-01">
    @include('hub.layout.header')

    <div class="content-wrapper container-fluid">
        @include('hub.layout.sidebar')
        @yield('content')
    </div>

    @include('hub.layout.footer')
    @stack('scripts')
</body>

</html>
