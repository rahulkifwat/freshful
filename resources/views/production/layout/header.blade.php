<nav class="navbar navbar-expand-lg main-navbar">
    <a class="navbar-brand" href="{{ route('production.dashboard') }}">
        <img src="{{ asset('assets/image/logo.png') }}" alt="Logo" style="height:40px;">
    </a>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-user-circle"></i>
                {{ Auth::guard('productions')->user()->name ?? 'Production Manager' }}
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="{{ route('production.profile') }}"><i class="fa fa-user"></i> Profile</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="{{ route('production.logout') }}"><i class="fa fa-sign-out"></i> Logout</a>
            </div>
        </li>
    </ul>
</nav>
