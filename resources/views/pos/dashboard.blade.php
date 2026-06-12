@extends('pos.layout.master')
@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <h2>Welcome, {{ $pos_user->name ?? 'POS User' }}</h2>
            <h5 class="text-muted">Your Hub: <strong>{{ $hub->hub ?? 'Not Assigned' }}</strong>
                @if($hub) &nbsp;|&nbsp; City: <strong>{{ $hub->city_name ?? '-' }}</strong>@endif
            </h5>

            <div class="row mt-4">
                <div class="col-md-2 col-sm-4 mb-3">
                    <a href="{{ route('pos.new_orders') }}" class="card text-center py-3 border-secondary text-decoration-none">
                        <div class="card-body p-2">
                            <h4 class="badge badge-secondary" style="font-size:1.4rem">{{ $counts['new_count'] ?? 0 }}</h4>
                            <p class="mb-0 mt-1">New Orders</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-2 col-sm-4 mb-3">
                    <a href="{{ route('pos.accepted_orders') }}" class="card text-center py-3 text-decoration-none">
                        <div class="card-body p-2">
                            <h4 class="badge badge-info" style="font-size:1.4rem">{{ $counts['accepted_count'] ?? 0 }}</h4>
                            <p class="mb-0 mt-1">Accepted</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-2 col-sm-4 mb-3">
                    <a href="{{ route('pos.unsettled_invoices') }}" class="card text-center py-3 text-decoration-none">
                        <div class="card-body p-2">
                            <h4 class="badge badge-warning" style="font-size:1.4rem">{{ $counts['unsettled_count'] ?? 0 }}</h4>
                            <p class="mb-0 mt-1">Unsettled</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-2 col-sm-4 mb-3">
                    <a href="{{ route('pos.ongoing_orders') }}" class="card text-center py-3 text-decoration-none">
                        <div class="card-body p-2">
                            <h4 class="badge badge-primary" style="font-size:1.4rem">{{ $counts['ongoing_count'] ?? 0 }}</h4>
                            <p class="mb-0 mt-1">Ongoing</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-2 col-sm-4 mb-3">
                    <a href="{{ route('pos.completed_orders') }}" class="card text-center py-3 text-decoration-none">
                        <div class="card-body p-2">
                            <h4 class="badge badge-success" style="font-size:1.4rem">{{ $counts['completed_count'] ?? 0 }}</h4>
                            <p class="mb-0 mt-1">Completed</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-2 col-sm-4 mb-3">
                    <a href="{{ route('pos.cancelled_orders') }}" class="card text-center py-3 text-decoration-none">
                        <div class="card-body p-2">
                            <h4 class="badge badge-danger" style="font-size:1.4rem">{{ $counts['cancelled_count'] ?? 0 }}</h4>
                            <p class="mb-0 mt-1">Cancelled</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
