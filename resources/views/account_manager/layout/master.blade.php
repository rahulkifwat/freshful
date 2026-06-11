<!DOCTYPE html>
<html lang="en">
@include('account_manager.layout.head')

<body class="index-page index-01">
    @include('account_manager.layout.header')

    <div class="content-wrapper container-fluid">
        @include('account_manager.layout.sidebar')
        @yield('content')
    </div>

    @include('account_manager.layout.footer')
    @stack('scripts')
</body>

</html>
