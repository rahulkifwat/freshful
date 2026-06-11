<!DOCTYPE html>
<html lang="en">
@include('admin.layout.head')

<body class="index-page index-01">
    @include('admin.layout.header')

    <div class="content-wrapper container-fluid">
        @include('admin.layout.sidebar')
        @yield('content')
    </div>


    @include('admin.layout.footer')
    @stack('scripts')
</body>

</html>