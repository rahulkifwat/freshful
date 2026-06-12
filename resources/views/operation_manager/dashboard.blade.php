@extends('operation_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h4 class="page-title">Dashboard</h4>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Buyers</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $buyers }}</div>
                            </div>
                            <div class="col-auto"><i class="fa fa-users fa-2x text-success"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Orders</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $orders }}</div>
                            </div>
                            <div class="col-auto"><i class="fa fa-shopping-cart fa-2x text-primary"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Today Orders</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $today_orders }}</div>
                            </div>
                            <div class="col-auto"><i class="fa fa-calendar fa-2x text-warning"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Products</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $products }}</div>
                            </div>
                            <div class="col-auto"><i class="fa fa-cubes fa-2x text-info"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header"><b>Quick Actions</b></div>
                    <div class="card-body">
                        <a href="{{ route('operation_manager.all_orders') }}" class="btn btn-primary mr-2 mb-2"><i class="fa fa-list"></i> All Orders</a>
                        <a href="{{ route('operation_manager.pending_orders') }}" class="btn btn-warning mr-2 mb-2"><i class="fa fa-clock-o"></i> Pending Orders</a>
                        <a href="{{ route('operation_manager.hub_inventory') }}" class="btn btn-success mr-2 mb-2"><i class="fa fa-archive"></i> Hub Inventory</a>
                        <a href="{{ route('operation_manager.all_customers') }}" class="btn btn-info mr-2 mb-2"><i class="fa fa-users"></i> Customers</a>
                        <a href="{{ route('operation_manager.inward_outward') }}" class="btn btn-secondary mr-2 mb-2"><i class="fa fa-exchange"></i> Inward/Outward</a>
                        <a href="{{ route('operation_manager.danger_stock') }}" class="btn btn-danger mr-2 mb-2"><i class="fa fa-exclamation-triangle"></i> Danger Stock</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
