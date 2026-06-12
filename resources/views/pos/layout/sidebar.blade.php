<div class="left-side-menu">
    <div class="slimscroll-menu">
        <div id="sidebar-menu">
            <ul class="metismenu" id="side-menu">
                <li class="menu-title">Orders</li>
                <li class="{{ request()->routeIs('pos.new_orders') ? 'active' : '' }}">
                    <a href="{{ route('pos.new_orders') }}">
                        <i class="fa fa-th"></i>
                        <span class="badge badge-secondary badge-pill float-right">{{ $counts['new_count'] ?? 0 }}</span>
                        <span> New Orders </span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('pos.accepted_orders') ? 'active' : '' }}">
                    <a href="{{ route('pos.accepted_orders') }}">
                        <i class="fa fa-th"></i>
                        <span class="badge badge-info badge-pill float-right">{{ $counts['accepted_count'] ?? 0 }}</span>
                        <span> Accepted Orders </span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('pos.unsettled_invoices') ? 'active' : '' }}">
                    <a href="{{ route('pos.unsettled_invoices') }}">
                        <i class="fa fa-th"></i>
                        <span class="badge badge-warning badge-pill float-right">{{ $counts['unsettled_count'] ?? 0 }}</span>
                        <span> Unsettled Invoices </span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('pos.ongoing_orders') ? 'active' : '' }}">
                    <a href="{{ route('pos.ongoing_orders') }}">
                        <i class="fa fa-th"></i>
                        <span class="badge badge-primary badge-pill float-right">{{ $counts['ongoing_count'] ?? 0 }}</span>
                        <span> Ongoing Orders </span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('pos.completed_orders') ? 'active' : '' }}">
                    <a href="{{ route('pos.completed_orders') }}">
                        <i class="fa fa-th"></i>
                        <span class="badge badge-success badge-pill float-right">{{ $counts['completed_count'] ?? 0 }}</span>
                        <span> Completed Orders </span>
                    </a>
                </li>
                <li class="{{ request()->routeIs('pos.cancelled_orders') ? 'active' : '' }}">
                    <a href="{{ route('pos.cancelled_orders') }}">
                        <i class="fa fa-th"></i>
                        <span class="badge badge-danger badge-pill float-right">{{ $counts['cancelled_count'] ?? 0 }}</span>
                        <span> Cancelled Orders </span>
                    </a>
                </li>
                <li class="menu-title">Reports</li>
                <li class="{{ request()->routeIs('pos.day_end_report') ? 'active' : '' }}">
                    <a href="{{ route('pos.day_end_report') }}">
                        <i class="fa fa-th"></i>
                        <span> Day End Report </span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="clearfix"></div>
    </div>
</div>
