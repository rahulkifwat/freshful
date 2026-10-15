{{-- Approve / Reject buttons: .js-driver-approve / .js-driver-reject with data-id and data-name. --}}
@push('scripts')
<script>
  (function ($) {
    function notify(type, msg) {
      if (typeof toastr !== 'undefined') { toastr[type](msg); } else { alert(msg); }
    }

    function errorMessage(xhr) {
      var r = xhr && xhr.responseJSON;
      if (r && r.errors) { return Object.values(r.errors)[0][0]; }
      return (r && r.message) || 'Network error. Please try again.';
    }

    // Disables every action button for the driver while a request runs (no double submits).
    function send($btn, url, data) {
      var $group = $('[data-id="' + $btn.data('id') + '"].js-driver-action');
      var label = $btn.html();
      $group.prop('disabled', true);
      $btn.html('<span class="spinner-border spinner-border-sm"></span> Processing…');

      $.post(url, data).done(function (res) {
        if (res.result) {
          notify('success', res.message);
          setTimeout(function () { location.reload(); }, 700);
        } else {
          notify('error', res.message || 'Action failed.');
          $group.prop('disabled', false); $btn.html(label);
        }
      }).fail(function (xhr) {
        notify('error', errorMessage(xhr));
        $group.prop('disabled', false); $btn.html(label);
      });
    }

    $(document).on('click', '.js-driver-approve', function () {
      var $btn = $(this);
      swal({
        title: 'Approve ' + $btn.data('name') + '?',
        text: 'The driver will be able to receive delivery orders.',
        icon: 'warning',
        buttons: ['Cancel', 'Approve']
      }).then(function (ok) {
        if (ok) { send($btn, '{{ route('admin.drivers.approve') }}', { id: $btn.data('id') }); }
      });
    });

    $(document).on('click', '.js-driver-reject', function () {
      var $btn = $(this);
      swal({
        title: 'Reject ' + $btn.data('name') + '?',
        text: 'Optionally enter a reason. The driver can see it.',
        icon: 'warning',
        content: { element: 'input', attributes: { placeholder: 'Rejection reason (optional)', maxlength: 1000 } },
        buttons: ['Cancel', 'Reject'],
        dangerMode: true
      }).then(function (reason) {
        // null = cancelled; '' = confirmed without a reason
        if (reason !== null) { send($btn, '{{ route('admin.drivers.reject') }}', { id: $btn.data('id'), reason: reason }); }
      });
    });
  })(jQuery);
</script>
@endpush
