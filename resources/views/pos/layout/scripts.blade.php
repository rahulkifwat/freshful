<script src="{{ url('assets/pos/js/vendor.min.js') }}"></script>
<script src="{{ url('assets/pos/js/app.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script>
toastr.options = { closeButton:true, progressBar:true, positionClass:'toast-top-right', timeOut:4000 };
</script>
@if(session('success'))<script>toastr.success("{{ session('success') }}");</script>@endif
@if(session('error'))<script>toastr.error("{{ session('error') }}");</script>@endif

<script>
function updateOrderStatus(order_id, status, amount_to_collect, amount_to_return) {
    $.post("{{ url('pos/ajax/update-order-status') }}", {
        _token: '{{ csrf_token() }}',
        order_id: order_id,
        order_status: status,
        amount_to_collect: amount_to_collect || '',
        amount_to_return: amount_to_return || ''
    }, function(r) {
        if (r.result) {
            swal({ type:'success', title: r.message, timer:1500 });
            setTimeout(function(){ location.reload(); }, 1500);
        } else {
            swal({ type:'error', title: r.message, timer:1500 });
        }
    }, 'json');
}
</script>
