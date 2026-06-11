<!DOCTYPE html>
<html lang="en">
@include('hr_manager.layout.head')

<body class="index-page index-01">
    @include('hr_manager.layout.header')

    <div class="content-wrapper container-fluid">
        @include('hr_manager.layout.sidebar')
        @yield('content')
    </div>

    @include('hr_manager.layout.footer')
    @stack('scripts')
</body>

</html>
