<aside class="left-panel">
  <nav class="navbar">
    <ul class="navbar-nav">

      <li class="nav-item dropdown active">
        <a class="nav-link" href="{{ route('hub.dashboard') }}">
          <i class="fa fa-dashboard"></i> <span class="menu-title">Dashboard</span>
        </a>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
          <i class="fa fa-stop-circle"></i><span class="menu-title">Orders</span>
        </a>
        <div class="dropdown-menu">
          <a class="nav-link" href="{{ route('hub.my_today_order') }}">Today's Orders</a>
          <a class="nav-link" href="{{ route('hub.scheduled_orders') }}">Scheduled Orders</a>
          <a class="nav-link" href="{{ route('hub.today_cancel_orders') }}">Cancelled Orders</a>
          <a class="nav-link" href="{{ route('hub.interhub_orders') }}">Inter-Hub Orders</a>
        </div>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
          <i class="fa fa-users"></i><span class="menu-title">Customers</span>
        </a>
        <div class="dropdown-menu">
          <a class="nav-link" href="{{ route('hub.all_customers') }}">All Customers</a>
          <a class="nav-link" href="{{ route('hub.customer_order') }}">Customer Orders</a>
          <a class="nav-link" href="{{ route('hub.wallet_history') }}">Wallet History</a>
        </div>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
          <i class="fa fa-cubes"></i><span class="menu-title">Inventory</span>
        </a>
        <div class="dropdown-menu">
          <a class="nav-link" href="{{ route('hub.hub_inventory') }}">Hub Inventory</a>
          <a class="nav-link" href="{{ route('hub.pending_inward') }}">Pending Inward</a>
          <a class="nav-link" href="{{ route('hub.accept_inward_stocks') }}">Accept Inward</a>
          <a class="nav-link" href="{{ route('hub.locked_inventory') }}">Locked Inventory</a>
        </div>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('hub.hub_transactions') }}">
          <i class="fa fa-exchange"></i> <span class="menu-title">Hub Transactions</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('hub.delivery_boy') }}">
          <i class="fa fa-motorcycle"></i> <span class="menu-title">Delivery Boy</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('hub.cash_deposit') }}">
          <i class="fa fa-money"></i> <span class="menu-title">Cash Deposit</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('hub.profile') }}">
          <i class="fa fa-user"></i> <span class="menu-title">Profile</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('hub.logout') }}">
          <i class="fa fa-sign-out"></i> <span class="menu-title">Logout</span>
        </a>
      </li>

    </ul>
  </nav>
</aside>
