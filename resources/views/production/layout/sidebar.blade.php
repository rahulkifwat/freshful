<aside class="left-panel">
    <nav class="navbar">
        <ul class="navbar-nav">
            <li class="nav-item {{ request()->routeIs('production.dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('production.dashboard') }}">
                    <i class="fa fa-dashboard"></i> <span class="menu-title">Dashboard</span>
                </a>
            </li>

            {{-- Account Management --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-book"></i><span class="menu-title">Account Management</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('production.view_cash_deposits_hub') ? 'active' : '' }}" href="{{ route('production.view_cash_deposits_hub') }}">Cash Deposits (Hub)</a>
                    <a class="nav-link {{ request()->routeIs('production.view_cash_deposits_all_hub') ? 'active' : '' }}" href="{{ route('production.view_cash_deposits_all_hub') }}">Cash Deposits (All Hubs)</a>
                    <a class="nav-link {{ request()->routeIs('production.sales_report') ? 'active' : '' }}" href="{{ route('production.sales_report') }}">Sales Report</a>
                    <a class="nav-link {{ request()->routeIs('production.view_wastage_report') ? 'active' : '' }}" href="{{ route('production.view_wastage_report') }}">View Wastage Report</a>
                    <a class="nav-link {{ request()->routeIs('production.view_wastage_report_hub_wise') ? 'active' : '' }}" href="{{ route('production.view_wastage_report_hub_wise') }}">Wastage Report Hub Wise</a>
                </div>
            </li>

            {{-- Inventory --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-area-chart"></i><span class="menu-title">Inventory</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('production.pending_inward') ? 'active' : '' }}" href="{{ route('production.pending_inward') }}">Pending Inward</a>
                    <a class="nav-link {{ request()->routeIs('production.hub_inventory') ? 'active' : '' }}" href="{{ route('production.hub_inventory') }}">Hub Inventory</a>
                    <a class="nav-link {{ request()->routeIs('production.interhub_orders') ? 'active' : '' }}" href="{{ route('production.interhub_orders') }}">InterHub Orders</a>
                    <a class="nav-link {{ request()->routeIs('production.interhub_moments_view') ? 'active' : '' }}" href="{{ route('production.interhub_moments_view') }}">InterHub Moments</a>
                    <a class="nav-link {{ request()->routeIs('production.locked_inventory') ? 'active' : '' }}" href="{{ route('production.locked_inventory') }}">Locked Inventory</a>
                    <a class="nav-link {{ request()->routeIs('production.scheduled_orders') ? 'active' : '' }}" href="{{ route('production.scheduled_orders') }}">Scheduled Orders</a>
                </div>
            </li>

            {{-- Customer --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-user"></i><span class="menu-title">Customer</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('production.all_customers') ? 'active' : '' }}" href="{{ route('production.all_customers') }}">Customers</a>
                </div>
            </li>

            {{-- Payment --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-credit-card"></i><span class="menu-title">Payment</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('production.online_payment_history') ? 'active' : '' }}" href="{{ route('production.online_payment_history') }}">Online Payment History</a>
                    <a class="nav-link {{ request()->routeIs('production.wallet_payment_history') ? 'active' : '' }}" href="{{ route('production.wallet_payment_history') }}">Wallet Payment History</a>
                </div>
            </li>

            {{-- Cash Deposit --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-money"></i><span class="menu-title">Cash Deposit</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('production.deposit_receipt') ? 'active' : '' }}" href="{{ route('production.deposit_receipt') }}">Add Deposit Receipt</a>
                    <a class="nav-link {{ request()->routeIs('production.cash_deposit') ? 'active' : '' }}" href="{{ route('production.cash_deposit') }}">View Cash Deposits</a>
                </div>
            </li>

            {{-- Communicator --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-calendar-check-o"></i><span class="menu-title">Communicator</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('production.inward_outward') ? 'active' : '' }}" href="{{ route('production.inward_outward') }}">Hub Inward/Outward</a>
                    <a class="nav-link {{ request()->routeIs('production.accept_inward_stocks') ? 'active' : '' }}" href="{{ route('production.accept_inward_stocks') }}">Accept Inward Stocks</a>
                    <a class="nav-link {{ request()->routeIs('production.request_outward_stocks') ? 'active' : '' }}" href="{{ route('production.request_outward_stocks') }}">Request Outward Stocks</a>
                </div>
            </li>

            {{-- Production Management --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-product-hunt"></i><span class="menu-title">Production Management</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('production.products') ? 'active' : '' }}" href="{{ route('production.products') }}">Products</a>
                    <a class="nav-link {{ request()->routeIs('production.wastage_reports') ? 'active' : '' }}" href="{{ route('production.wastage_reports') }}">Wastage Reports</a>
                    <a class="nav-link {{ request()->routeIs('production.production_records') ? 'active' : '' }}" href="{{ route('production.production_records') }}">Production</a>
                </div>
            </li>

            {{-- Marketing --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-industry"></i><span class="menu-title">Marketing</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('production.news_letters') ? 'active' : '' }}" href="{{ route('production.news_letters') }}">News Letters</a>
                    <a class="nav-link {{ request()->routeIs('production.banner') ? 'active' : '' }}" href="{{ route('production.banner') }}">Banners</a>
                    <a class="nav-link {{ request()->routeIs('production.home_offers') ? 'active' : '' }}" href="{{ route('production.home_offers') }}">Home Offers</a>
                    <a class="nav-link {{ request()->routeIs('production.promotions') ? 'active' : '' }}" href="{{ route('production.promotions') }}">Promotions</a>
                    <a class="nav-link {{ request()->routeIs('production.certificate') ? 'active' : '' }}" href="{{ route('production.certificate') }}">Certificate</a>
                    <a class="nav-link {{ request()->routeIs('production.rating_reviews') ? 'active' : '' }}" href="{{ route('production.rating_reviews') }}">Rating & Reviews</a>
                </div>
            </li>

            {{-- Customer Care --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-phone"></i><span class="menu-title">Customer Care</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('production.grievance') ? 'active' : '' }}" href="{{ route('production.grievance') }}">View Grievance</a>
                    <a class="nav-link {{ request()->routeIs('production.grievance_categories') ? 'active' : '' }}" href="{{ route('production.grievance_categories') }}">Grievance Categories</a>
                    <a class="nav-link {{ request()->routeIs('production.add_wallet_money') ? 'active' : '' }}" href="{{ route('production.add_wallet_money') }}">Add Wallet Money</a>
                    <a class="nav-link {{ request()->routeIs('production.withdraw_money') ? 'active' : '' }}" href="{{ route('production.withdraw_money') }}">Withdraw Money</a>
                    <a class="nav-link {{ request()->routeIs('production.express_order_time_slot') ? 'active' : '' }}" href="{{ route('production.express_order_time_slot') }}">Express Time Slot</a>
                    <a class="nav-link {{ request()->routeIs('production.scheduled_order_time_slot') ? 'active' : '' }}" href="{{ route('production.scheduled_order_time_slot') }}">Scheduled Time Slot</a>
                </div>
            </li>

            {{-- Wastage --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-trash"></i><span class="menu-title">Wastage</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('production.sku_wastage') ? 'active' : '' }}" href="{{ route('production.sku_wastage') }}">SKU Wastage</a>
                    <a class="nav-link {{ request()->routeIs('production.submit_wastage_report') ? 'active' : '' }}" href="{{ route('production.submit_wastage_report') }}">Submit Wastage Report</a>
                </div>
            </li>

            {{-- Danger Stock --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fa fa-tasks"></i><span class="menu-title">Danger Stock</span>
                </a>
                <div class="dropdown-menu">
                    <a class="nav-link {{ request()->routeIs('production.danger_stock') ? 'active' : '' }}" href="{{ route('production.danger_stock') }}">View Danger Stock</a>
                    <a class="nav-link {{ request()->routeIs('production.transfer_danger_stock') ? 'active' : '' }}" href="{{ route('production.transfer_danger_stock') }}">Transfer Danger Stock</a>
                </div>
            </li>

            <li class="nav-item {{ request()->routeIs('production.all_orders') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('production.all_orders') }}"><i class="fa fa-list"></i> <span class="menu-title">All Orders</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('production.delivery_boy') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('production.delivery_boy') }}"><i class="fa fa-motorcycle"></i> <span class="menu-title">Delivery Boy</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('production.hub_kml_list') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('production.hub_kml_list') }}"><i class="fa fa-map-marker"></i> <span class="menu-title">Hub KML List</span></a>
            </li>
            <li class="nav-item {{ request()->routeIs('production.setting') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('production.setting') }}"><i class="fa fa-cog"></i> <span class="menu-title">Setting</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('production.logout') }}"><i class="fa fa-sign-out"></i> <span class="menu-title">Logout</span></a>
            </li>
        </ul>
    </nav>
</aside>
