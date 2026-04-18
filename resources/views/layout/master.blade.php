<!DOCTYPE html>
<html lang="en">
@include('layout.head')

<body class="index-page">
    @include('layout.header')

    
    @yield('content')


    @include('layout.footer')
    @stack('scripts')
</body>

</html>