<div class="navbar-custom">
    <ul class="list-unstyled topnav-menu float-right mb-0">
        <li class="dropdown notification-list">
            <a class="nav-link dropdown-toggle nav-user mr-0 waves-effect" data-toggle="dropdown" href="#">
                <span class="pro-user-name ml-1">
                    {{ Auth::guard('pos_users')->user()->name ?? 'POS User' }}
                    <i class="mdi mdi-chevron-down"></i>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right profile-dropdown">
                <a href="{{ route('pos.logout') }}" class="dropdown-item notify-item">
                    <i class="fe-log-out"></i> <span>Logout</span>
                </a>
            </div>
        </li>
    </ul>
    <div class="logo-box">
        <a href="{{ route('pos.dashboard') }}" class="logo text-center">
            <span class="logo-lg">
                <img src="{{ asset('assets/image/logo.png') }}" alt="" height="42">
            </span>
            <span class="logo-sm">
                <img src="{{ asset('assets/image/logo.png') }}" alt="" height="30">
            </span>
        </a>
    </div>
</div>
