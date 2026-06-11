 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>




 <script src="{{ url('assets/admin/js/jquery-3.2.1.slim.min.js') }}"></script>
 <script src="{{ url('assets/admin/js/plugins.js') }}"></script>
 <script src="{{ url('assets/admin/js/tables/jquery.dataTables.min.js') }}"></script>
 <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.3.1/js/dataTables.buttons.min.js"></script>
 <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
 <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.3.1/js/buttons.html5.min.js"></script>
 <script src="{{ url('assets/admin/js/tables/dataTables.bootstrap4.min.js') }}"></script>


 <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js"></script>
 <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/additional-methods.min.js"></script>

 <!--Morris Chart-->
 <script src="{{ url('assets/admin/js/charts/morris/raphael-min.js ') }}"></script>
 <script src="{{ url('assets/admin/js/charts/morris/morris.js') }}"></script>

 <!--Chartist Chart-->
 <script src="{{ url('assets/admin/js/charts/chartist/chartist.min.js') }}"></script>
 <script src="{{ url('assets/admin/js/charts/chartist/chartist-plugin-legend.js') }}"></script>

 <!--Sparkline Chart-->
 <script src="{{ url('assets/admin/js/charts/sparkline/jquery.sparkline.min.js') }}"></script>

 <!--Flot Chart-->
 <script src="{{ url('assets/admin/js/charts/flot/jquery.flot.js') }}"></script>
 <script src="{{ url('assets/admin/js/charts/flot/jquery.flot.pie.js') }}"></script>
 <script src="{{ url('assets/admin/js/charts/flot/jquery.flot.resize.js') }}"></script>
 <script src="{{ url('assets/admin/js/charts/flot/jquery.flot.spline.js') }}"></script>
 <script src="{{ url('assets/admin/js/widgets/charts.init.js') }}"></script>

 <!--popups-->
 <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" integrity="sha512-VEd+nq25CkR676O+pLBnDW09R7VQX9Mdiij052gVCp5yVH3jGtH70Ho/UUv4mJDsEdTvqRCFZg0NKGiojGnUCw==" crossorigin="anonymous"></script>

 <!--For Weather-->
 <script src="{{ url('assets/admin/js/owl.carousel.min.js') }}"></script>
 <script src="{{ url('assets/admin/js/widgets/weather.js') }}"></script>

 <!--For Calendar-->
 <script src="{{ url('assets/admin/js/calendar/moment.js') }}"></script>
 <script src="{{ url('assets/admin/js/calendar/fullcalendar.min.js') }}"></script>

 <script src="{{ url('assets/admin/js/index/index-01.js') }}"></script>
 <script src="{{ url('assets/admin/js/main.js') }}"></script>

 <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>

 

 <script>
   $(document).ready(function() {
     "use strict";
     $('.data-table').DataTable();
   });
 </script>

 <script>
   // Universal admin AJAX handlers — wired from data-attributes on the markup.
   (function ($) {
     var token = document.querySelector('meta[name="csrf-token"]');
     token = token ? token.getAttribute('content') : '';
     $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token } });

     // Status toggle (.change_status checkbox with data-table + data-id).
     // Skip elements without data-table — those are order-status selects
     // handled by a separate listener below.
     $(document).on('change', '.change_status', function () {
       var $el = $(this);
       if (!$el.data('table')) { return; }
       $.post('{{ url('admin/ajax/change-status') }}', {
         table: $el.data('table'),
         id:    $el.data('id'),
         value: $el.val()
       }).done(function (res) {
         if (res.result) {
           $el.val(res.status);
           if (typeof toastr !== 'undefined') toastr.success(res.message);
         } else {
           $el.prop('checked', !$el.prop('checked'));
           if (typeof toastr !== 'undefined') toastr.error(res.message || 'Update failed.');
         }
       }).fail(function () {
         $el.prop('checked', !$el.prop('checked'));
         if (typeof toastr !== 'undefined') toastr.error('Network error.');
       });
     });

     // Delete record (.deleteRecord button with data-table + data-id).
     $(document).on('click', '.deleteRecord', function () {
       var $btn = $(this);
       if (typeof swal === 'function') {
         swal({
           title: 'Delete this record?', icon: 'warning',
           buttons: ['Cancel', 'Delete'], dangerMode: true
         }).then(function (ok) { if (ok) doDelete($btn); });
       } else if (window.confirm('Delete this record?')) {
         doDelete($btn);
       }
     });

     function doDelete($btn) {
       $.post('{{ url('admin/ajax/delete-record') }}', {
         table: $btn.data('table'),
         id:    $btn.data('id')
       }).done(function (res) {
         if (res.result) {
           $btn.closest('tr').fadeOut(200, function () { $(this).remove(); });
           if (typeof toastr !== 'undefined') toastr.success(res.message);
         } else if (typeof toastr !== 'undefined') {
           toastr.error(res.message || 'Delete failed.');
         }
       }).fail(function () {
         if (typeof toastr !== 'undefined') toastr.error('Network error.');
       });
     }

     // Order status dropdown (used on /admin/order).
     $(document).on('change', '.change_status_order, select.change_status[data-order_id]', function () {
       var $sel = $(this);
       $.post('{{ url('admin/ajax/change-order-status') }}', {
         id:           $sel.data('id'),
         order_id:     $sel.data('order_id'),
         order_status: $sel.val()
       }).done(function (res) {
         if (res.result) {
           if (typeof toastr !== 'undefined') toastr.success(res.message);
         } else if (typeof toastr !== 'undefined') {
           toastr.error(res.message || 'Update failed.');
         }
       }).fail(function () {
         if (typeof toastr !== 'undefined') toastr.error('Network error.');
       });
     });
   })(jQuery);
 </script>

