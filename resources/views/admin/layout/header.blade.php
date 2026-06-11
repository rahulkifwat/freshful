 <header class="top-header media">
     <div class="top-left mr-3">
         <a class="navbar-brand" href="dashboard.php">
             <img src="{{ asset('assets/image/logo.png') }}" alt="Logo" class="  img-fluid" style="width:125px">

            <!-- <img src="https://shreethemes.in/orderit/layouts/assets/images/client/1.jpg" alt="Site Logo"  ></a>  -->
     </div><!-- /.top-left -->

     <div class="top-right media-body">
         <div class="left-content float-left">
             <a href="#" class="sidenav-toggle mr-2" data-toggle="push-menu" role="button">
                 <span class="sr-only">Toggle navigation</span>
                 <i class="fa fa-bars"></i>
             </a>
         </div><!-- /.left-content -->

         <div class="right-content float-right">
             <!--<div class="country dropdown">-->
             <!--  <div id="country_selector"></div>-->
             <!--</div>-->
             <!-- /.country -->



             <!--<div class="dropdown notifications-menu">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <i class="fa fa-bell-o"></i>
            <span class="count">4</span>
          </a>
          <ul class="dropdown-menu">
            <li class="header">You have 4 notifications</li>
            <li>
              <ul class="dropdown-content">
                <li>
                  <a href="#">
                    <i class="fa fa-users alert-primary"></i> Curabitur id eros quis nunc suscipit blandit.
                  </a>
                </li>
                <li>
                  <a href="#">
                    <i class="fa fa-warning alert-secondary"></i> Duis malesuada justo eu sapien elementum, in semper diam posuere.
                  </a>
                </li>
                <li>
                  <a href="#">
                    <i class="fa fa-shopping-cart alert-success"></i> In gravida mauris et nisi
                  </a>
                </li>
                <li>
                  <a href="#">
                    <i class="fa fa-user alert-danger"></i> Praesent eu lacus in libero dictum fermentum.
                  </a>
                </li>
              </ul>
            </li>
            <li class="footer"><a href="#">View all</a></li>
          </ul>
        </div>-->

             <!-- /.dropdown -->
             <div class="dropdown user-menu">
                 <a href="#" class="dropdown-toggle" data-toggle="dropdown">

                     <h4 class="name">{{ Auth::guard('admin')->user()->name ?? 'Admin' }}</h4>
                 </a>
                 <ul class="dropdown-menu">
                     <li><a href="{{ url('admin/profile') }}"><i class="fa fa-user"></i> My Profile</a></li>
                     <li><a href="{{ route('admin.logout') }}"><i class="fa fa-power-off"></i> Logout</a></li>
                 </ul>
             </div>

         </div><!-- /.right-content -->
     </div><!-- /.top-right -->
 </header><!-- /.top-header -->
 <style type="text/css">
     .selected-flag {
         display: none;
     }
 </style>