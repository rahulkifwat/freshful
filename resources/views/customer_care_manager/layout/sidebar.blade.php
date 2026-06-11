<aside class="left-panel">
  <nav class="navbar">
    <ul class="navbar-nav">

      <li class="nav-item dropdown active">
        <a class="nav-link" href="{{ route('customer_care_manager.dashboard') }}">
          <i class="fa fa-dashboard"></i> <span class="menu-title">Dashboard</span>
        </a>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
          <i class="fa fa-stop-circle"></i><span class="menu-title">Orders</span>
        </a>
        <div class="dropdown-menu">
          <a class="nav-link" href="{{ route('customer_care_manager.all_orders') }}">All Orders</a>
          <a class="nav-link" href="{{ route('customer_care_manager.scheduled_orders') }}">Scheduled Orders</a>
          <a class="nav-link" href="{{ route('customer_care_manager.customer_order') }}">Customer Order</a>
        </div>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('customer_care_manager.all_customers') }}">
          <i class="fa fa-users"></i> <span class="menu-title">All Customers</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('customer_care_manager.products') }}">
          <i class="fa fa-product-hunt"></i> <span class="menu-title">Products</span>
        </a>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
          <i class="fa fa-exclamation-circle"></i><span class="menu-title">Grievance</span>
        </a>
        <div class="dropdown-menu">
          <a class="nav-link" href="{{ route('customer_care_manager.grievance') }}">All Grievances</a>
          <a class="nav-link" href="{{ route('customer_care_manager.grievance_categories') }}">Categories</a>
        </div>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
          <i class="fa fa-cubes"></i><span class="menu-title">Inventory</span>
        </a>
        <div class="dropdown-menu">
          <a class="nav-link" href="{{ route('customer_care_manager.hub_inventory') }}">Hub Inventory</a>
          <a class="nav-link" href="{{ route('customer_care_manager.pending_inward') }}">Pending Inward</a>
          <a class="nav-link" href="{{ route('customer_care_manager.inward_outward') }}">Inward / Outward</a>
        </div>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('customer_care_manager.danger_stock') }}">
          <i class="fa fa-warning text-danger"></i> <span class="menu-title text-danger">Danger Stock</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('customer_care_manager.wastage_reports') }}">
          <i class="fa fa-trash"></i> <span class="menu-title">Wastage Reports</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('customer_care_manager.delivery_boy') }}">
          <i class="fa fa-motorcycle"></i> <span class="menu-title">Delivery Boy</span>
        </a>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
          <i class="fa fa-wallet"></i><span class="menu-title">Wallet</span>
        </a>
        <div class="dropdown-menu">
          <a class="nav-link" href="{{ route('customer_care_manager.wallet_history') }}">Wallet History</a>
          <a class="nav-link" href="{{ route('customer_care_manager.withdraw_money') }}">Withdraw Money</a>
        </div>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
          <i class="fa fa-bar-chart"></i><span class="menu-title">Reports</span>
        </a>
        <div class="dropdown-menu">
          <a class="nav-link" href="{{ route('customer_care_manager.sales_report') }}">Sales Report</a>
          <a class="nav-link" href="{{ route('customer_care_manager.cash_deposit') }}">Cash Deposit</a>
          <a class="nav-link" href="{{ route('customer_care_manager.online_payment_history') }}">Online Payment</a>
          <a class="nav-link" href="{{ route('customer_care_manager.rating_reviews') }}">Ratings & Reviews</a>
        </div>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('customer_care_manager.news_letters') }}">
          <i class="fa fa-envelope"></i> <span class="menu-title">News Letters</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('customer_care_manager.profile') }}">
          <i class="fa fa-user"></i> <span class="menu-title">Profile</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{ route('customer_care_manager.logout') }}">
          <i class="fa fa-sign-out"></i> <span class="menu-title">Logout</span>
        </a>
      </li>

    </ul>
  </nav>
</aside>
