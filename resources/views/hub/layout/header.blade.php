<header class="top-header media">
    <div class="top-left mr-3">
        <a class="navbar-brand" href="{{ route('hub.dashboard') }}">
            <img src="{{ asset('assets/image/logo.png') }}" alt="Logo" class="img-fluid" style="width:125px">
        </a>
    </div>
    <div class="top-right media-body">
        <div class="left-content float-left">
            <a href="#" class="sidenav-toggle mr-2" data-toggle="push-menu" role="button">
                <span class="sr-only">Toggle navigation</span>
                <i class="fa fa-bars"></i>
            </a>
        </div>
        <div class="right-content float-right">
            <div class="dropdown user-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <h4 class="name">{{ Auth::guard('hub_users')->user()->name ?? 'Hub User' }}</h4>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="{{ route('hub.profile') }}"><i class="fa fa-user"></i> My Profile</a></li>
                    <li><a href="{{ route('hub.logout') }}"><i class="fa fa-power-off"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>
<style type="text/css">.selected-flag { display: none; }</style>
