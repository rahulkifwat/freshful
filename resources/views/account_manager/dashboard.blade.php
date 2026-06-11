@extends('account_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="contents-inner">
          <div class="row">

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Customers</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($total_buyers) }}</div>
                    </div>
                    <div class="col-auto"><i class="fa fa-users fa-2x text-gray-300"></i></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Orders</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($total_orders) }}</div>
                    </div>
                    <div class="col-auto"><i class="fa fa-shopping-cart fa-2x text-gray-300"></i></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Products</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($total_products) }}</div>
                    </div>
                    <div class="col-auto"><i class="fa fa-product-hunt fa-2x text-gray-300"></i></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Today's Orders</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($today_orders) }}</div>
                    </div>
                    <div class="col-auto"><i class="fa fa-calendar fa-2x text-gray-300"></i></div>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <div class="row mt-3">
            <div class="col-12">
              <div class="card shadow">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Quick Links</h6></div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-3 mb-2"><a href="{{ route('account_manager.all_customers') }}" class="btn btn-outline-primary btn-block">Customers</a></div>
                    <div class="col-md-3 mb-2"><a href="{{ route('account_manager.all_orders') }}" class="btn btn-outline-success btn-block">Orders</a></div>
                    <div class="col-md-3 mb-2"><a href="{{ route('account_manager.sales_report') }}" class="btn btn-outline-warning btn-block">Sales Report</a></div>
                    <div class="col-md-3 mb-2"><a href="{{ route('account_manager.danger_stock') }}" class="btn btn-outline-danger btn-block">Danger Stock</a></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection
