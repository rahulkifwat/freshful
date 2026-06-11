<!DOCTYPE html>
<html lang="en">
@include('customer_care_manager.layout.head')

<body class="index-page index-01">
    @include('customer_care_manager.layout.header')

    <div class="content-wrapper container-fluid">
        @include('customer_care_manager.layout.sidebar')
        @yield('content')
    </div>

    @include('customer_care_manager.layout.footer')
    @stack('scripts')
</body>

</html>
