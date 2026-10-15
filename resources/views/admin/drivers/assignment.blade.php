@extends('admin.layout.master')

@section('content')
@php
  $tabs = ['unassigned' => 'Unassigned', 'assigned' => 'Assigned', 'all' => 'All assignable'];
@endphp
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="contents-inner">
          <div class="row">
            <div class="col-12">
              <div class="section-content">
                <div class="content-head">
                  <h4 class="content-title">Order Assignment</h4>
                </div>

                <div class="content-details show">
                  <p class="text-muted mb-3">
                    Orders in status <strong>{{ implode(' / ', $statuses) }}</strong> can be assigned.
                    The driver can be changed until the order is dispatched.
                  </p>

                  <ul class="nav nav-pills mb-3 flex-wrap">
                    @foreach($tabs as $key => $label)
                      <li class="nav-item">
                        <a class="nav-link {{ $filter === $key ? 'active' : '' }}"
                           href="{{ route('admin.drivers.assignment', array_filter(['filter' => $key, 'search' => request('search')])) }}">{{ $label }}</a>
                      </li>
                    @endforeach
                  </ul>

                  <form method="get" action="{{ route('admin.drivers.assignment') }}" class="mb-3">
                    <input type="hidden" name="filter" value="{{ $filter }}">
                    <div class="row">
                      <div class="col-md-5 col-sm-8 mb-2">
                        <input type="text" name="search" class="form-control" placeholder="Search by order ID, customer name or phone" value="{{ request('search') }}">
                      </div>
                      <div class="col-md-3 col-sm-4 mb-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if(request('search'))
                          <a href="{{ route('admin.drivers.assignment', ['filter' => $filter]) }}" class="btn btn-outline-secondary">Reset</a>
                        @endif
                      </div>
                    </div>
                  </form>

                  <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>Order</th>
                          <th>Customer</th>
                          <th>Delivery address</th>
                          <th>Ordered</th>
                          <th>Delivery</th>
                          <th>Status</th>
                          <th>Driver</th>
                          <th style="min-width: 130px">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($orders as $o)
                          @php $assigned = (int) $o->rider_id > 0; @endphp
                          <tr>
                            <td>
                              <a href="{{ url('admin/order?order_id='.$o->order_id) }}">{{ $o->order_id }}</a>
                              <br><small class="text-muted">{{ $o->items }} item(s) · ₹{{ $o->total_amount }}</small>
                            </td>
                            <td>{{ $o->buyer_name ?: '—' }}<br><small class="text-muted">{{ $o->buyer_phone ?: $o->address_phone }}</small></td>
                            <td>
                              {{ $o->address_name }}{{ $o->address_name ? ', ' : '' }}{{ collect([$o->street_name, $o->locality, $o->landmark, $o->city])->filter()->implode(', ') ?: '—' }}
                              @if($o->address_phone)<br><small class="text-muted">{{ $o->address_phone }}</small>@endif
                              @if($o->hub_name)<br><small class="text-muted">Hub: {{ $o->hub_name }}</small>@endif
                            </td>
                            <td>{{ $o->date_added ? \Illuminate\Support\Carbon::parse($o->date_added)->format('d M Y, h:i a') : '—' }}</td>
                            <td>
                              {{ $o->delivery_type ?: '—' }}
                              @if($o->schedule_date)<br><small class="text-muted">{{ $o->schedule_date }} {{ $o->schedule_time }}</small>@endif
                              <br><small class="text-muted">{{ $o->payment_type ?: '—' }} · {{ $o->payment_status ?: '—' }}</small>
                            </td>
                            <td><span class="badge badge-info">{{ $o->order_status }}</span></td>
                            <td>
                              @if($assigned)
                                <span class="badge badge-success">Assigned</span><br>
                                {{ $o->rider_name ?: 'Driver #'.$o->rider_id }}
                                @if($o->rider_phone)<br><small class="text-muted">{{ $o->rider_phone }}</small>@endif
                              @else
                                <span class="badge badge-warning">Unassigned</span>
                              @endif
                            </td>
                            <td>
                              <button type="button" class="btn btn-sm {{ $assigned ? 'btn-outline-primary' : 'btn-primary' }} js-open-assign"
                                      data-order="{{ $o->order_id }}" data-rider="{{ (int) $o->rider_id }}"
                                      data-rider-name="{{ $assigned ? ($o->rider_name ?: 'Driver #'.$o->rider_id) : '' }}">
                                {{ $assigned ? 'Change driver' : 'Assign driver' }}
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                              {{ request('search') ? 'No orders match your search.' : ($filter === 'unassigned' ? 'No orders are waiting for a driver.' : 'No assignable orders.') }}
                            </td>
                          </tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>

                  <div class="mt-3">{{ $orders->links() }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Assign driver modal --}}
<div class="modal fade" id="assignModal" tabindex="-1" role="dialog" aria-labelledby="assignModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assignModalTitle">Assign driver</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <p class="mb-2" id="assignCurrent"></p>
        <input type="text" class="form-control mb-3" id="driverSearch" placeholder="Search approved drivers by name, phone or vehicle number" autocomplete="off">
        <div id="driverState" class="text-center text-muted py-3"></div>
        <div class="table-responsive">
          <table class="table table-hover table-sm mb-0" id="driverTable" style="display:none">
            <thead><tr><th></th><th>Driver</th><th>Duty</th><th>Phone</th><th>Vehicle</th><th>Open orders</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmAssign" disabled>Assign</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function ($) {
    var current = { order: null, rider: 0, riderName: '' };
    var selected = null;
    var searchTimer = null;
    var lastRequest = null;

    function notify(type, msg) { if (typeof toastr !== 'undefined') { toastr[type](msg); } else { alert(msg); } }
    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

    function setState(text, isError) {
      $('#driverState').toggleClass('text-danger', !!isError).toggleClass('text-muted', !isError).html(text).show();
      $('#driverTable').hide();
    }

    function loadDrivers() {
      selected = null;
      $('#confirmAssign').prop('disabled', true);
      setState('<span class="spinner-border spinner-border-sm"></span> Loading drivers…');
      if (lastRequest) { lastRequest.abort(); }

      lastRequest = $.get('{{ route('admin.drivers.eligible') }}', { search: $('#driverSearch').val() })
        .done(function (res) {
          var drivers = (res && res.drivers) || [];
          if (!drivers.length) {
            setState($('#driverSearch').val() ? 'No approved drivers match your search.' : 'There are no approved, active drivers yet.');
            return;
          }
          var rows = drivers.map(function (d) {
            var isCurrent = d.id === current.rider;
            return '<tr class="js-driver-row' + (isCurrent ? ' table-secondary' : '') + '" data-id="' + d.id + '" data-name="' + esc(d.name) + '" style="cursor:' + (isCurrent ? 'not-allowed' : 'pointer') + '">'
              + '<td><input type="radio" name="driver_pick" value="' + d.id + '"' + (isCurrent ? ' disabled' : '') + '></td>'
              + '<td>' + esc(d.name) + (isCurrent ? ' <span class="badge badge-secondary">current</span>' : '') + '</td>'
              + '<td>' + (d.working_status === 'on_duty' ? '<span class="badge badge-primary">On duty</span>' : '<span class="badge badge-light border">Off duty</span>') + '</td>'
              + '<td>' + esc(d.phone || '—') + '</td>'
              + '<td>' + esc([d.vehicle_type, d.vehicle_number].filter(Boolean).join(' · ') || '—') + '</td>'
              + '<td>' + esc(d.open_orders) + '</td></tr>';
          });
          $('#driverTable tbody').html(rows.join(''));
          $('#driverState').hide();
          $('#driverTable').show();
        })
        .fail(function (xhr, status) {
          if (status !== 'abort') { setState('Could not load drivers. <a href="#" class="js-retry">Try again</a>', true); }
        });
    }

    $(document).on('click', '.js-open-assign', function () {
      var $b = $(this);
      current = { order: String($b.data('order')), rider: parseInt($b.data('rider'), 10) || 0, riderName: $b.data('rider-name') || '' };
      $('#assignModalTitle').text((current.rider ? 'Change driver for ' : 'Assign driver to ') + current.order);
      $('#assignCurrent').html(current.rider ? 'Currently assigned to <strong>' + esc(current.riderName) + '</strong>.' : 'This order has no driver yet.');
      $('#confirmAssign').text(current.rider ? 'Change driver' : 'Assign');
      $('#driverSearch').val('');
      $('#assignModal').modal('show');
      loadDrivers();
    });

    $('#driverSearch').on('input', function () { clearTimeout(searchTimer); searchTimer = setTimeout(loadDrivers, 300); });
    $(document).on('click', '.js-retry', function (e) { e.preventDefault(); loadDrivers(); });

    $(document).on('click', '.js-driver-row', function () {
      var $r = $(this);
      var $radio = $r.find('input[type=radio]');
      if ($radio.prop('disabled')) { return; }
      $radio.prop('checked', true);
      $('.js-driver-row').removeClass('table-active');
      $r.addClass('table-active');
      selected = { id: parseInt($r.data('id'), 10), name: $r.data('name') };
      $('#confirmAssign').prop('disabled', false);
    });

    $('#confirmAssign').on('click', function () {
      if (!selected) { return; }
      var $btn = $(this);
      var verb = current.rider ? 'Change the driver of ' : 'Assign ';
      swal({
        title: verb + current.order + (current.rider ? ' to ' : ' to ') + selected.name + '?',
        text: current.rider ? 'It will be removed from ' + current.riderName + '.' : '',
        icon: 'warning',
        buttons: ['Cancel', 'Confirm']
      }).then(function (ok) {
        if (!ok) { return; }
        var label = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving…');

        $.post('{{ route('admin.drivers.assign') }}', {
          order_id: current.order, driver_id: selected.id, expected_rider_id: current.rider
        }).done(function (res) {
          if (res.result) {
            notify('success', res.message);
            $('#assignModal').modal('hide');
            setTimeout(function () { location.reload(); }, 600);
          } else {
            notify('error', res.message || 'Assignment failed.');
            $btn.prop('disabled', false).html(label);
          }
        }).fail(function (xhr) {
          var r = xhr.responseJSON;
          notify('error', (r && r.errors && Object.values(r.errors)[0][0]) || (r && r.message) || 'Network error. Please try again.');
          $btn.prop('disabled', false).html(label);
        });
      });
    });
  })(jQuery);
</script>
@endpush
