<script src="{{ url('assets/admin/js/jquery-3.2.1.slim.min.js') }}"></script>
<script src="{{ url('assets/admin/js/plugins.js') }}"></script>
<script src="{{ url('assets/admin/js/main.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
toastr.options = { closeButton:true, progressBar:true, positionClass:'toast-top-right', timeOut:4000 };
</script>
@if(session('success'))<script>toastr.success("{{ session('success') }}");</script>@endif
@if(session('error'))<script>toastr.error("{{ session('error') }}");</script>@endif

<script>
$(document).on('change', '.change_status', function() {
    var table = $(this).data('table'), id = $(this).data('id'), val = $(this).val();
    $.post("{{ url('operation_manager/ajax/change-status') }}", {_token:'{{ csrf_token() }}', table:table, id:id, status:val},
        function(r){ if(r.result) toastr.success(r.message); else toastr.error(r.message); }, 'json');
});
$(document).on('click', '.deleteRecord', function() {
    if(!confirm('Are you sure?')) return;
    var table = $(this).data('table'), id = $(this).data('id'), row = $(this).closest('tr');
    $.post("{{ url('operation_manager/ajax/delete-record') }}", {_token:'{{ csrf_token() }}', table:table, id:id},
        function(r){ if(r.result){ row.remove(); toastr.success(r.message); } else toastr.error(r.message); }, 'json');
});
$(document).on('change', '.change_order_status', function() {
    var id = $(this).data('id'), val = $(this).val();
    $.post("{{ url('operation_manager/ajax/change-order-status') }}", {_token:'{{ csrf_token() }}', order_id:id, status:val},
        function(r){ if(r.result) toastr.success(r.message); else toastr.error(r.message); }, 'json');
});
</script>
@stack('scripts')
