 <aside class="left-panel">
   <nav class="navbar">
     <ul class="navbar-nav">
       <li class="nav-item dropdown active  ">
         <a class="nav-link " href="{{ url('/admin/dashboard') }}">
           <i class="fa fa-dashboard"></i> <span class="menu-title">Dashboard</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/all_orders') }} ">
           <i class="fa fa-stop-circle"></i> <span class="menu-title">ALL Orders</span>
         </a>
       </li>
       <li class="nav-item">
         <a class="nav-link" href="{{ url('/admin/order') }}">
           <i class="fa fa-stop-circle"></i> <span class="menu-title">Orders</span>
         </a>
       </li>
       <li class="nav-item dropdown  ">
         <a class="nav-link " href="{{ url('/admin/products') }}">
           <i class="fa fa-product-hunt"></i> <span class="menu-title">Products</span>
         </a>
       </li>
       <li class="nav-item dropdown ">
         <a class="nav-link " href="{{ url('/admin/inventory') }}">
           <i class="fa fa-product-hunt"></i> <span class="menu-title">Inventory</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/buyers') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">Buyers</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/pos_users') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">POS Users</span>
         </a>
       </li>
       <li class="nav-item ">
         <a class="nav-link" href="{{ url('/admin/hub_users') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">HUB Users</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/marketing_managers') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">Marketing Managers</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/production_user') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">Production Users</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/planning_managers') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">Planning Managers</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/area_managers') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">Area Managers</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/country_managers') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">Country Managers</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/account_managers') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">Account Managers</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/customer_care_managers') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">Customer Care Managers</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/hr_managers') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">HR Managers</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/operation_managers') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">Operation Managers</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/certificate') }}">
           <i class="fa fa-user-o"></i> <span class="menu-title">Certificate</span>
         </a>
       </li>
       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/inventory_report_detail') }}">
           <i class="fa fa-file-text-o"></i> <span class="menu-title text-danger">Inventory Report Detail</span>
         </a>
       </li>
       <li class="nav-item ">
         <a class="nav-link" href="{{ url('/admin/sale_summary') }}">
           <i class="fa fa-shopping-cart"></i> <span class="menu-title">Sales Summary</span>
         </a>
       </li>
       <li class="nav-item dropdown">
         <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
           <i class="icon icon-basic-webpage-multiple"></i><span class="menu-title">Master</span>
         </a>
         <div class="dropdown-menu">
           <a class="nav-link" href="{{ url('/admin/add_category') }}">Category </a>
           <a class="nav-link" href="{{ url('/admin/category') }}">Sub Category </a>
           <a class="nav-link" href="{{ url('/admin/coupons_and_deals') }}">Coupons and deals</a>
           <a class="nav-link" href="{{ url('/admin/items') }}">Items</a>
           <a class="nav-link" href="{{ url('/admin/product_tag') }}">Product Tag</a>
           <a class="nav-link" href="{{ url('/admin/product_unit') }}">Product Unit</a>
           <a class="nav-link" href="{{ url('/admin/product_filter') }}">Product filter</a>
           <a class="nav-link" href="{{ url('/admin/products') }}">Products</a>
           <a class="nav-link" href="{{ url('/admin/banner') }}">Banner</a>
           <a class="nav-link" href="{{ url('/admin/app_banner') }}">App Banner</a>
           <a class="nav-link" href="{{ url('/admin/city') }}">City</a>
           <a class="nav-link" href="{{ url('/admin/delivery_price') }}">Delivery Price</a>
           <a class="nav-link" href="{{ url('/admin/hub') }}">HUB</a>
           <a class="nav-link" href="{{ url('/admin/home_offers') }}">Home offers</a>
           <a class="nav-link" href="{{ url('/admin/promotions') }}">Pomotions</a>
           <a class="nav-link" href="{{ url('/admin/privacy_policy') }}">Privacy Policy</a>
           <a class="nav-link" href="{{ url('/admin/push') }}">Push Notification</a>
         </div>
       </li>
       <li class="nav-item dropdown">
         <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
           <i class="icon icon-user"></i><span class="menu-title">Roles</span>
         </a>
         <div class="dropdown-menu">
           <a class="nav-link" href="{{ url('/admin/role_type') }}">Role Type</a>
         </div>
       </li>

       <li class="nav-item dropdown">
         <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
           <i class="icon icon-user"></i><span class="menu-title">Policies</span>
         </a>
         <div class="dropdown-menu">
           <a class="nav-link" href="policies?type=privacy-policy">Privacy Policy</a>
           <a class="nav-link" href="policies?type=shipping_policy">Shipping Policy</a>
           <a class="nav-link" href="policies?type=terms">Terms & Conditions</a>
           <a class="nav-link" href="policies?type=about">About</a>
           <a class="nav-link" href="policies?type=refund_policy">Refund Policy</a>
           <a class="nav-link" href="policies?type=faq">Faq</a>
         </div>
       </li>

       <li class="nav-item dropdown">
         <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
           <i class="icon icon-user"></i><span class="menu-title">Queries</span>
         </a>
         <div class="dropdown-menu">
           <a class="nav-link" href="{{ url('/admin/news_letters') }}">News letters</a>
           <a class="nav-link" href="{{ url('/admin/contact_us') }}">Contact Us</a>
           <a class="nav-link" href="{{ url('/admin/frenchisee_enquiry') }}">Frenchisee Enquiry</a>
         </div>
       </li>

       <li class="nav-item  ">
         <a class="nav-link" href="{{ url('/admin/setting') }}">
           <i class="fa fa-cog"></i> <span class="menu-title">Setting</span>
         </a>
       </li>
       <li class="nav-item">
         <a class="nav-link" href="{{ url('/admin/logout') }}">
           <i class="fa fa-sign-out"></i> <span class="menu-title">Logout</span>
         </a>
       </li>
     </ul>
   </nav>
 </aside>