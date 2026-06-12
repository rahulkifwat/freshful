@extends('production.layout.master')
@section('content')
<div class="dashboard-contents">
    <div class="contents-inner">
        <div class="row">
            <div class="col-md-3">
                <div class="statistic-box m-0">
                    <h4 class="statistic-title float-left">Buyers</h4>
                    <div class="statistic-details">
                        <span class="count float-left">{{ $total_buyers }}</span>
                        <span class="statistic-icon color-primary float-right"><i class="fa fa-users"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="statistic-box m-0">
                    <h4 class="statistic-title float-left">Orders</h4>
                    <div class="statistic-details">
                        <span class="count float-left">{{ $total_orders }}</span>
                        <span class="statistic-icon color-purple float-right"><i class="fa fa-shopping-cart"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="statistic-box m-0">
                    <h4 class="statistic-title float-left">Today Orders</h4>
                    <div class="statistic-details">
                        <span class="count float-left">{{ $today_orders }}</span>
                        <span class="statistic-icon color-success float-right"><i class="fa fa-calendar"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="statistic-box m-0">
                    <h4 class="statistic-title float-left">Products</h4>
                    <div class="statistic-details">
                        <span class="count float-left">{{ $total_products }}</span>
                        <span class="statistic-icon color-danger float-right"><i class="fa fa-cube"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="section-content">
                    <div class="content-head">
                        <h4 class="content-title">Quick Actions</h4>
                    </div>
                    <div class="content-details show">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('production.all_orders') }}" class="btn btn-outline-primary btn-block">All Orders</a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('production.products') }}" class="btn btn-outline-success btn-block">Products</a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('production.production_records') }}" class="btn btn-outline-info btn-block">Production Records</a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('production.sales_report') }}" class="btn btn-outline-warning btn-block">Sales Report</a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('production.hub_inventory') }}" class="btn btn-outline-secondary btn-block">Hub Inventory</a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('production.delivery_boy') }}" class="btn btn-outline-dark btn-block">Delivery Boy</a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('production.wastage_reports') }}" class="btn btn-outline-danger btn-block">Wastage Reports</a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="{{ route('production.all_customers') }}" class="btn btn-outline-primary btn-block">Customers</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
