@extends('operation_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Submit Wastage Report</h4>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-header"><b>Add Wastage Report</b></div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('operation_manager.submit_wastage_report') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Hub</label>
                            <select name="hub_id" class="form-control" required>
                                <option value="">Select Hub</option>
                                @foreach($hubs as $h)
                                <option value="{{ $h->id }}">{{ $h->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Product / SKU</label>
                            <select name="product_id" class="form-control" required>
                                <option value="">Select Product</option>
                                @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Quantity Wasted</label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Reason</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Reason for wastage"></textarea>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Date</label>
                            <input type="date" name="wastage_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Submit Report</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
