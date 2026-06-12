@extends('planning_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Transfer Danger Stock</h4>
            </div>
        </div>
        <div class="card shadow mb-4">
            <div class="card-header"><b>Transfer Stock</b></div>
            <div class="card-body">
                <form method="POST" action="{{ route('planning_manager.transfer_danger_stock') }}">
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
                            <label>From Hub</label>
                            <select name="from_hub" class="form-control" required>
                                <option value="">Select Hub</option>
                                @foreach($hubs as $h)
                                <option value="{{ $h->id }}">{{ $h->name }}</option>
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
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-8 form-group">
                            <label>Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Remarks (optional)">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger"><i class="fa fa-exchange"></i> Transfer Stock</button>
                </form>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-header"><b>Transfer History</b></div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>From Hub</th>
                            <th>To Hub</th>
                            <th>Quantity</th>
                            <th>Remarks</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $i => $row)
                        <tr>
                            <td>{{ $transfers->firstItem() + $i }}</td>
                            <td>{{ $row->product_name ?? '-' }}</td>
                            <td>{{ $row->from_hub ?? '-' }}</td>
                            <td>{{ $row->to_hub ?? '-' }}</td>
                            <td>{{ $row->quantity }}</td>
                            <td>{{ $row->remarks ?? '-' }}</td>
                            <td>{{ $row->created_at ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">No transfer history</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $transfers->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
