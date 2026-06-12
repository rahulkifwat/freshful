@extends('operation_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Request Outward Stocks</h4>
            </div>
        </div>
        <div class="card shadow mb-4">
            <div class="card-header"><b>Request Stock Transfer</b></div>
            <div class="card-body">
                <form method="POST" action="{{ route('operation_manager.request_outward_stocks') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Product</label>
                            <select name="product_id" class="form-control" required>
                                <option value="">Select Product</option>
                                @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>To Hub</label>
                            <select name="to_hub" class="form-control" required>
                                <option value="">Select Hub</option>
                                @foreach($hubs as $h)
                                <option value="{{ $h->id }}">{{ $h->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" class="form-control" min="1" required placeholder="Quantity">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane"></i> Submit Request</button>
                </form>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-header"><b>Previous Requests</b></div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>To Hub</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Requested On</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $i => $row)
                        <tr>
                            <td>{{ $requests->firstItem() + $i }}</td>
                            <td>{{ $row->product_name ?? '-' }}</td>
                            <td>{{ $row->to_hub ?? '-' }}</td>
                            <td>{{ $row->quantity }}</td>
                            <td>
                                <span class="badge badge-{{ $row->status == 'accepted' ? 'success' : ($row->status == 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($row->status) }}
                                </span>
                            </td>
                            <td>{{ $row->created_at ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">No outward requests found</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $requests->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
