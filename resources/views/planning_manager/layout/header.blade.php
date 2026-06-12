<nav class="navbar navbar-expand-lg main-navbar">
    <a class="navbar-brand" href="{{ route('planning_manager.dashboard') }}">
        <img src="{{ asset('assets/image/logo.png') }}" alt="Logo" style="height:40px;">
    </a>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-user-circle"></i>
                {{ Auth::guard('planning_managers')->user()->name ?? 'Planning Manager' }}
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="{{ route('planning_manager.profile') }}"><i class="fa fa-user"></i> Profile</a>
                <a class="dropdown-item" href="{{ route('planning_manager.setting') }}"><i class="fa fa-cog"></i> Settings</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="{{ route('planning_manager.logout') }}"><i class="fa fa-sign-out"></i> Logout</a>
            </div>
        </li>
    </ul>
</nav>
