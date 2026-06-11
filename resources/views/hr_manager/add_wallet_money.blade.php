@extends('hr_manager.layout.master')
@section('content')
<div class="right-panel">
    <div class="content">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="page-title">Add Wallet Money</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('hr_manager.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Add Wallet Money</li>
                        </ul>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="row">
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">Search Buyer</h4></div>
                        <div class="card-body">
                            <form method="GET" action="{{ route('hr_manager.add_wallet_money') }}">
                                <div class="form-group">
                                    <label>Buyer ID</label>
                                    <input type="number" name="buyer_id" class="form-control"
                                           value="{{ $buyer_id }}" placeholder="Enter buyer ID" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Search</button>
                            </form>
                        </div>
                    </div>
                </div>

                @if($buyer)
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title">Add Money — {{ $buyer->name ?? 'Buyer #'.$buyer_id }}</h4></div>
                        <div class="card-body">
                            <p><strong>Name:</strong> {{ $buyer->name ?? '—' }}</p>
                            <p><strong>Phone:</strong> {{ $buyer->phone ?? '—' }}</p>
                            <p><strong>Current Wallet:</strong> ₹{{ number_format($buyer->wallet_amount ?? 0, 2) }}</p>
                            <hr>
                            <form method="POST" action="{{ route('hr_manager.add_wallet_money.submit') }}">
                                @csrf
                                <input type="hidden" name="buyer_id" value="{{ $buyer->id }}">
                                <div class="form-group">
                                    <label>Amount (₹)</label>
                                    <input type="number" name="amount" class="form-control" min="1" step="0.01"
                                           placeholder="Enter amount" required>
                                </div>
                                <div class="form-group">
                                    <label>Note (optional)</label>
                                    <input type="text" name="note" class="form-control" placeholder="Reason for adding">
                                </div>
                                <button type="submit" class="btn btn-success btn-block">Add Money</button>
                            </form>
                        </div>
                    </div>
                </div>
                @elseif($buyer_id)
                    <div class="col-md-7">
                        <div class="alert alert-warning">No buyer found with ID {{ $buyer_id }}.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
