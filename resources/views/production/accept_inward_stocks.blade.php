@extends('production.layout.master')
@section('content')
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h4 class="page-title">Accept Inward Stocks</h4>
            </div>
        </div>
        <div class="card shadow">
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Quantity</th>
                            <th>From Hub</th>
                            <th>Requested On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stocks as $i => $row)
                        <tr>
                            <td>{{ $stocks->firstItem() + $i }}</td>
                            <td>{{ $row->product_name ?? '-' }}</td>
                            <td>{{ $row->sku ?? '-' }}</td>
                            <td>{{ $row->quantity ?? '-' }}</td>
                            <td>{{ $row->from_hub ?? '-' }}</td>
                            <td>{{ $row->created_at ?? '-' }}</td>
                            <td>
                                <button class="btn btn-sm btn-success acceptStock" data-id="{{ $row->id }}"><i class="fa fa-check"></i> Accept</button>
                                <button class="btn btn-sm btn-danger rejectStock" data-id="{{ $row->id }}"><i class="fa fa-times"></i> Reject</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center">No pending inward stocks</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $stocks->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
$(document).on('click', '.acceptStock,.rejectStock', function(){
    var id = $(this).data('id'), action = $(this).hasClass('acceptStock') ? 'accept' : 'reject';
    if(!confirm('Are you sure to ' + action + ' this stock?')) return;
    $.post("{{ url('production/ajax/inward-stock-action') }}", {_token:'{{ csrf_token() }}', id:id, action:action}, function(r){
        if(r.result){ location.reload(); toastr.success(r.message); } else toastr.error(r.message);
    },'json');
});
</script>
@endpush
@endsection
