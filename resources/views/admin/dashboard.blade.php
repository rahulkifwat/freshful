@extends('admin.layout.master')
@section('content')
<div class="dashboard-contents">
    <div class="contents-inner">
        <div class="row">
            <div class="col-12">
                <div class="section-content">

                    <div class="row">
                        <div class="col-md-3">
                            <div class="statistic-box m-0">
                                <h4 class="statistic-title float-left">Buyers</h4>
                                <div class="statistic-details">
                                    <span class="count float-left">{{ $buyer_count }}</span>
                                    <span class="statistic-icon color-primary float-right"><i class="pe-7s-users"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="statistic-box m-0">
                                <h4 class="statistic-title float-left">Revenue</h4>
                                <div class="statistic-details">
                                    <span class="count float-left">{{ number_format((float) $total_revenue, 2) }}</span>
                                    <span class="statistic-icon color-primary float-right"><i class="pe-7s-ticket"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="statistic-box m-0">
                                <h4 class="statistic-title float-left">Total Orders</h4>
                                <div class="statistic-details">
                                    <span class="count float-left">{{ $total_orders }}</span>
                                    <span class="statistic-icon color-purple float-right"><i class="pe-7s-credit"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="statistic-box m-0">
                                <h4 class="statistic-title float-left">Products</h4>
                                <div class="statistic-details">
                                    <span class="count float-left">{{ $product_count }}</span>
                                    <span class="statistic-icon color-purple float-right"><i class="pe-7s-albums"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="statistic-box m-0">
                                <h4 class="statistic-title float-left">Pending Orders</h4>
                                <div class="statistic-details">
                                    <span class="count float-left">{{ $pending_orders }}</span>
                                    <span class="statistic-icon color-warning float-right"><i class="pe-7s-clock"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="statistic-box m-0">
                                <h4 class="statistic-title float-left">Ongoing Orders</h4>
                                <div class="statistic-details">
                                    <span class="count float-left">{{ $ongoing_orders }}</span>
                                    <span class="statistic-icon color-info float-right"><i class="pe-7s-repeat"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="statistic-box m-0">
                                <h4 class="statistic-title float-left">Delivered</h4>
                                <div class="statistic-details">
                                    <span class="count float-left">{{ $complete_orders }}</span>
                                    <span class="statistic-icon color-success float-right"><i class="pe-7s-check"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="statistic-box m-0">
                                <h4 class="statistic-title float-left">Cancelled</h4>
                                <div class="statistic-details">
                                    <span class="count float-left">{{ $cancel_orders }}</span>
                                    <span class="statistic-icon color-danger float-right"><i class="pe-7s-close-circle"></i></span>
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
