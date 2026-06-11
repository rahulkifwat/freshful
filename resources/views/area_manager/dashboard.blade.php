@extends('area_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="contents-inner">
          <div class="row">

            <div class="col-xl-2 col-md-4 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Customers</div>
                  <div class="h5 mb-0 font-weight-bold">{{ number_format($total_buyers) }}</div>
                </div>
              </div>
            </div>

            <div class="col-xl-2 col-md-4 mb-4">
              <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Orders</div>
                  <div class="h5 mb-0 font-weight-bold">{{ number_format($total_orders) }}</div>
                </div>
              </div>
            </div>

            <div class="col-xl-2 col-md-4 mb-4">
              <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Today's Orders</div>
                  <div class="h5 mb-0 font-weight-bold">{{ number_format($today_orders) }}</div>
                </div>
              </div>
            </div>

            <div class="col-xl-2 col-md-4 mb-4">
              <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Products</div>
                  <div class="h5 mb-0 font-weight-bold">{{ number_format($total_products) }}</div>
                </div>
              </div>
            </div>

            <div class="col-xl-2 col-md-4 mb-4">
              <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Danger Stock</div>
                  <div class="h5 mb-0 font-weight-bold">{{ number_format($danger_count) }}</div>
                </div>
              </div>
            </div>

            <div class="col-xl-2 col-md-4 mb-4">
              <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Wastage</div>
                  <div class="h5 mb-0 font-weight-bold">{{ number_format($wastage_count) }}</div>
                </div>
              </div>
            </div>

          </div>

          <div class="row mt-2">
            <div class="col-12">
              <div class="card shadow">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Quick Links</h6></div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-2 mb-2"><a href="{{ route('area_manager.all_orders') }}" class="btn btn-outline-success btn-block btn-sm">All Orders</a></div>
                    <div class="col-md-2 mb-2"><a href="{{ route('area_manager.all_customers') }}" class="btn btn-outline-primary btn-block btn-sm">Customers</a></div>
                    <div class="col-md-2 mb-2"><a href="{{ route('area_manager.danger_stock') }}" class="btn btn-outline-danger btn-block btn-sm">Danger Stock</a></div>
                    <div class="col-md-2 mb-2"><a href="{{ route('area_manager.wastage_reports') }}" class="btn btn-outline-warning btn-block btn-sm">Wastage</a></div>
                    <div class="col-md-2 mb-2"><a href="{{ route('area_manager.hub_inventory') }}" class="btn btn-outline-info btn-block btn-sm">Inventory</a></div>
                    <div class="col-md-2 mb-2"><a href="{{ route('area_manager.sales_report') }}" class="btn btn-outline-secondary btn-block btn-sm">Sales Report</a></div>
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
