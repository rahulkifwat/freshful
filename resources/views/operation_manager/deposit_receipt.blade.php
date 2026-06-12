@extends('operation_manager.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="page-title">Add Deposit Receipt</h4>
            </div>
        </div>
        <div class="card shadow mb-4">
            <div class="card-header"><b>Submit Cash Deposit Receipt</b></div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('operation_manager.deposit_receipt') }}" enctype="multipart/form-data">
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
                            <label>Amount</label>
                            <input type="number" name="amount" step="0.01" class="form-control" required placeholder="Amount">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Deposit Date</label>
                            <input type="date" name="deposit_date" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" placeholder="Bank Name">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Reference / Transaction No</label>
                            <input type="text" name="transaction_no" class="form-control" placeholder="Reference Number">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Receipt Image</label>
                            <input type="file" name="receipt_image" class="form-control-file" accept="image/*">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fa fa-upload"></i> Submit Receipt</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
