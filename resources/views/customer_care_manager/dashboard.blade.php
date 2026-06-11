@extends('customer_care_manager.layout.master')
@section('content')
<div class="right-panel">
    <div class="content">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="page-title">Dashboard</h3>
                        <ul class="breadcrumb"><li class="breadcrumb-item active">Dashboard</li></ul>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="row">
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="dash-widget-header">
                                <span class="dash-widget-icon text-primary border-primary"><i class="fa fa-users"></i></span>
                                <div class="dash-count"><h3>{{ $total_buyers }}</h3></div>
                            </div>
                            <div class="dash-widget-info">
                                <h6 class="text-muted">Total Buyers</h6>
                                <div class="progress progress-sm"><div class="progress-bar bg-primary" style="width:100%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="dash-widget-header">
                                <span class="dash-widget-icon text-success border-success"><i class="fa fa-shopping-cart"></i></span>
                                <div class="dash-count"><h3>{{ $total_orders }}</h3></div>
                            </div>
                            <div class="dash-widget-info">
                                <h6 class="text-muted">Total Orders</h6>
                                <div class="progress progress-sm"><div class="progress-bar bg-success" style="width:100%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="dash-widget-header">
                                <span class="dash-widget-icon text-warning border-warning"><i class="fa fa-calendar"></i></span>
                                <div class="dash-count"><h3>{{ $today_orders }}</h3></div>
                            </div>
                            <div class="dash-widget-info">
                                <h6 class="text-muted">Today's Orders</h6>
                                <div class="progress progress-sm"><div class="progress-bar bg-warning" style="width:100%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="dash-widget-header">
                                <span class="dash-widget-icon text-danger border-danger"><i class="fa fa-exclamation-circle"></i></span>
                                <div class="dash-count"><h3>{{ $open_grievances }}</h3></div>
                            </div>
                            <div class="dash-widget-info">
                                <h6 class="text-muted">Open Grievances</h6>
                                <div class="progress progress-sm"><div class="progress-bar bg-danger" style="width:100%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="dash-widget-header">
                                <span class="dash-widget-icon text-info border-info"><i class="fa fa-list-alt"></i></span>
                                <div class="dash-count"><h3>{{ $total_grievances }}</h3></div>
                            </div>
                            <div class="dash-widget-info">
                                <h6 class="text-muted">Total Grievances</h6>
                                <div class="progress progress-sm"><div class="progress-bar bg-info" style="width:100%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="dash-widget-header">
                                <span class="dash-widget-icon text-warning border-warning"><i class="fa fa-money"></i></span>
                                <div class="dash-count"><h3>{{ $withdraw_pending }}</h3></div>
                            </div>
                            <div class="dash-widget-info">
                                <h6 class="text-muted">Pending Withdrawals</h6>
                                <div class="progress progress-sm"><div class="progress-bar bg-warning" style="width:100%"></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-4 mb-3">
                    <a href="{{ route('customer_care_manager.grievance') }}" class="btn btn-danger btn-block"><i class="fa fa-exclamation-circle"></i> View Grievances</a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="{{ route('customer_care_manager.withdraw_money') }}" class="btn btn-warning btn-block"><i class="fa fa-money"></i> Withdraw Requests</a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="{{ route('customer_care_manager.all_orders') }}" class="btn btn-primary btn-block"><i class="fa fa-shopping-cart"></i> All Orders</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
