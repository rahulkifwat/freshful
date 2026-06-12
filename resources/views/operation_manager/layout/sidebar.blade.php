<aside class="left-panel">
    <nav class="navbar">
        <ul class="navbar-nav">
            <li class="nav-item {{ request()->routeIs('operation_manager.dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('operation_manager.dashboard') }}">
                    <i class="fa fa-dashboard"></i> <span class="menu-title">Dashboard</span>
                </a>
            </li>

            {{-- Account Management --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-book"></i><span class="menu-title">Account Management</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('operation_manager.view_cash_deposits_hub') ? 'active' : '' }}" href="{{ route('operation_manager.view_cash_deposits_hub') }}">Cash Deposits (Hub)</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.view_cash_deposits_all_hub') ? 'active' : '' }}" href="{{ route('operation_manager.view_cash_deposits_all_hub') }}">Cash Deposits (All Hubs)</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.sales_report') ? 'active' : '' }}" href="{{ route('operation_manager.sales_report') }}">Sales Report</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.view_wastage_report') ? 'active' : '' }}" href="{{ route('operation_manager.view_wastage_report') }}">View Wastage Report</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.view_wastage_report_hub_wise') ? 'active' : '' }}" href="{{ route('operation_manager.view_wastage_report_hub_wise') }}">Wastage Report Hub Wise</a>
                </div>
            </li>

            {{-- Inventory --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-area-chart"></i><span class="menu-title">Inventory</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('operation_manager.pending_inward') ? 'active' : '' }}" href="{{ route('operation_manager.pending_inward') }}">Pending Inward</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.hub_inventory') ? 'active' : '' }}" href="{{ route('operation_manager.hub_inventory') }}">Hub Inventory</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.interhub_orders') ? 'active' : '' }}" href="{{ route('operation_manager.interhub_orders') }}">InterHub Orders</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.interhub_moments_view') ? 'active' : '' }}" href="{{ route('operation_manager.interhub_moments_view') }}">InterHub Moments</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.locked_inventory') ? 'active' : '' }}" href="{{ route('operation_manager.locked_inventory') }}">Locked Inventory</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.scheduled_orders') ? 'active' : '' }}" href="{{ route('operation_manager.scheduled_orders') }}">Scheduled Orders</a>
                </div>
            </li>

            {{-- Communicator --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-calendar-check-o"></i><span class="menu-title">Communicator</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('operation_manager.inward_outward') ? 'active' : '' }}" href="{{ route('operation_manager.inward_outward') }}">Hub Inward/Outward</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.accept_inward_stocks') ? 'active' : '' }}" href="{{ route('operation_manager.accept_inward_stocks') }}">Accept Inward Stocks</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.request_outward_stocks') ? 'active' : '' }}" href="{{ route('operation_manager.request_outward_stocks') }}">Request Outward Stocks</a>
                </div>
            </li>

            {{-- Customer --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-user"></i><span class="menu-title">Customer</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('operation_manager.all_customers') ? 'active' : '' }}" href="{{ route('operation_manager.all_customers') }}">Customers</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.customer_order') ? 'active' : '' }}" href="{{ route('operation_manager.customer_order') }}">Customer Orders</a>
                </div>
            </li>

            {{-- Payment --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-credit-card"></i><span class="menu-title">Payment</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('operation_manager.online_payment_history') ? 'active' : '' }}" href="{{ route('operation_manager.online_payment_history') }}">Online Payment History</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.wallet_payment_history') ? 'active' : '' }}" href="{{ route('operation_manager.wallet_payment_history') }}">Wallet Payment History</a>
                </div>
            </li>

            {{-- Cash Deposit --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-money"></i><span class="menu-title">Cash Deposit</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('operation_manager.deposit_receipt') ? 'active' : '' }}" href="{{ route('operation_manager.deposit_receipt') }}">Add Deposit Receipt</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.cash_deposit') ? 'active' : '' }}" href="{{ route('operation_manager.cash_deposit') }}">View Cash Deposits</a>
                </div>
            </li>

            {{-- Production Management --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-product-hunt"></i><span class="menu-title">Production</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('operation_manager.products') ? 'active' : '' }}" href="{{ route('operation_manager.products') }}">Products</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.wastage_reports') ? 'active' : '' }}" href="{{ route('operation_manager.wastage_reports') }}">Wastage Reports</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.production') ? 'active' : '' }}" href="{{ route('operation_manager.production') }}">Production</a>
                </div>
            </li>

            {{-- Marketing --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-industry"></i><span class="menu-title">Marketing</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('operation_manager.news_letters') ? 'active' : '' }}" href="{{ route('operation_manager.news_letters') }}">News Letters</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.banner') ? 'active' : '' }}" href="{{ route('operation_manager.banner') }}">Banners</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.home_offers') ? 'active' : '' }}" href="{{ route('operation_manager.home_offers') }}">Home Offers</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.promotions') ? 'active' : '' }}" href="{{ route('operation_manager.promotions') }}">Promotions</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.rating_reviews') ? 'active' : '' }}" href="{{ route('operation_manager.rating_reviews') }}">Rating & Reviews</a>
                </div>
            </li>

            {{-- Customer Care --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-phone"></i><span class="menu-title">Customer Care</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('operation_manager.grievance') ? 'active' : '' }}" href="{{ route('operation_manager.grievance') }}">View Grievance</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.grievance_categories') ? 'active' : '' }}" href="{{ route('operation_manager.grievance_categories') }}">Grievance Categories</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.add_wallet_money') ? 'active' : '' }}" href="{{ route('operation_manager.add_wallet_money') }}">Add Wallet Money</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.withdraw_money') ? 'active' : '' }}" href="{{ route('operation_manager.withdraw_money') }}">Withdraw Money</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.express_order_time_slot') ? 'active' : '' }}" href="{{ route('operation_manager.express_order_time_slot') }}">Express Time Slot</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.scheduled_order_time_slot') ? 'active' : '' }}" href="{{ route('operation_manager.scheduled_order_time_slot') }}">Scheduled Time Slot</a>
                </div>
            </li>

            {{-- Wastage --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-trash"></i><span class="menu-title">Wastage</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('operation_manager.sku_wastage') ? 'active' : '' }}" href="{{ route('operation_manager.sku_wastage') }}">SKU Wastage</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.submit_wastage_report') ? 'active' : '' }}" href="{{ route('operation_manager.submit_wastage_report') }}">Submit Wastage Report</a>
                </div>
            </li>

            {{-- Danger Stock --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-tasks"></i><span class="menu-title">Danger Stock</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('operation_manager.danger_stock') ? 'active' : '' }}" href="{{ route('operation_manager.danger_stock') }}">View Danger Stock</a>
                    <a class="nav-link {{ request()->routeIs('operation_manager.transfer_danger_stock') ? 'active' : '' }}" href="{{ route('operation_manager.transfer_danger_stock') }}">Transfer Danger Stock</a>
                </div>
            </li>

            <li class="nav-item {{ request()->routeIs('operation_manager.all_orders') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('operation_manager.all_orders') }}"><i class="fa fa-list"></i> <span class="menu-title">All Orders</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('operation_manager.pending_orders') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('operation_manager.pending_orders') }}"><i class="fa fa-file"></i> <span class="menu-title">Pending Orders</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('operation_manager.order_status') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('operation_manager.order_status') }}"><i class="fa fa-file-text-o"></i> <span class="menu-title">Order Status</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('operation_manager.delivery_boy') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('operation_manager.delivery_boy') }}"><i class="fa fa-motorcycle"></i> <span class="menu-title">Delivery Boy</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('operation_manager.hub_kml_list') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('operation_manager.hub_kml_list') }}"><i class="fa fa-map-marker"></i> <span class="menu-title">Hub KML List</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('operation_manager.sku_report') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('operation_manager.sku_report') }}"><i class="fa fa-bar-chart"></i> <span class="menu-title">SKU Report</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('operation_manager.profile') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('operation_manager.profile') }}"><i class="fa fa-user"></i> <span class="menu-title">Profile</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('operation_manager.logout') }}"><i class="fa fa-sign-out"></i> <span class="menu-title">Logout</span></a>
            </li>
        </ul>
    </nav>
</aside>
