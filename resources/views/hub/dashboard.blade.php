@extends('hub.layout.master')
@section('content')
<div class="right-panel">
    <div class="content">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="page-title">Dashboard @if($hub) — {{ $hub->hub }} @endif</h3>
                        <ul class="breadcrumb"><li class="breadcrumb-item active">Dashboard</li></ul>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="row">
                <div class="col-xl-2 col-sm-4 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="text-primary">{{ $stats['today_orders'] }}</h3>
                            <p class="text-muted mb-0">Today's Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-sm-4 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="text-warning">{{ $stats['scheduled_orders'] }}</h3>
                            <p class="text-muted mb-0">Scheduled</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-sm-4 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="text-info">{{ $stats['dispatched'] }}</h3>
                            <p class="text-muted mb-0">Dispatched</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-sm-4 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="text-success">{{ $stats['delivered'] }}</h3>
                            <p class="text-muted mb-0">Delivered</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-sm-4 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="text-danger">{{ $stats['cancelled'] }}</h3>
                            <p class="text-muted mb-0">Cancelled</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-sm-4 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="text-secondary">{{ $stats['pending'] }}</h3>
                            <p class="text-muted mb-0">Pending</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-3 mb-3">
                    <a href="{{ route('hub.my_today_order') }}" class="btn btn-primary btn-block"><i class="fa fa-calendar"></i> Today's Orders</a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="{{ route('hub.hub_inventory') }}" class="btn btn-info btn-block"><i class="fa fa-cubes"></i> Hub Inventory</a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="{{ route('hub.delivery_boy') }}" class="btn btn-warning btn-block"><i class="fa fa-motorcycle"></i> Delivery Boy</a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="{{ route('hub.hub_transactions') }}" class="btn btn-secondary btn-block"><i class="fa fa-exchange"></i> Hub Transactions</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
