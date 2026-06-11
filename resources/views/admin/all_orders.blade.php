@extends('admin.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="contents-inner">

          <div class="row">
            <div class="col-md-12">
              <div class="section-content">
                <div class="content-head">
                  <h4 class="content-title">Filter</h4>
                </div>

                <div class="content-details show">
                  <div id="pay-invoice" class="card pay-invoice">
                    <div class="card-body">
                      <form class="form" method="get" action="{{ route('admin.all_orders') }}">
                        <div class="row p-15">

                          <div class="col-md-3">
                            <div class="form-group">
                              <label>Date From</label>
                              <input class="form-control" type="date" name="date_from" value="{{ request('date_from') }}">
                            </div>
                          </div>

                          <div class="col-md-3">
                            <div class="form-group">
                              <label>Date To</label>
                              <input class="form-control" type="date" name="date_to" value="{{ request('date_to') }}">
                            </div>
                          </div>

                          <div class="col-md-3">
                            <div class="form-group">
                              <label>City</label>
                              <select id="city_id" name="city_id" class="form-control">
                                <option value="">Select City</option>
                                @foreach($cities as $city)
                                  <option value="{{ $city->id }}"
                                    {{ (string) request('city_id') === (string) $city->id ? 'selected' : '' }}>
                                    {{ $city->city }}
                                  </option>
                                @endforeach
                              </select>
                            </div>
                          </div>

                          <div class="col-md-3">
                            <div class="form-group">
                              <label>Hub</label>
                              <select id="hub_id" name="hub_id" class="form-control">
                                <option value="">Select Hub</option>
                                @foreach($hubs as $hub)
                                  <option value="{{ $hub->id }}"
                                    data-city="{{ $hub->city_id }}"
                                    {{ (string) request('hub_id') === (string) $hub->id ? 'selected' : '' }}>
                                    {{ $hub->hub }}
                                  </option>
                                @endforeach
                              </select>
                            </div>
                          </div>

                          <div class="col-md-3">
                            <div class="form-group">
                              <label>Customer Name</label>
                              <input class="form-control" type="text" name="customer"
                                     placeholder="Enter customer name"
                                     value="{{ request('customer') }}">
                            </div>
                          </div>

                          <div class="col-md-3">
                            <div class="form-group">
                              <label>Customer Mobile</label>
                              <input class="form-control" type="text" name="customer_mobile" maxlength="10"
                                     placeholder="Enter customer mobile"
                                     value="{{ request('customer_mobile') }}">
                            </div>
                          </div>

                          <div class="col-md-3">
                            <div class="form-group">
                              <label>Status</label>
                              <select name="order_status" class="form-control">
                                <option value="">Select</option>
                                @foreach(['Order Pending','Order Cancel','Order Placed','Order Processed','Order Shipped','Order Delivered'] as $s)
                                  <option value="{{ $s }}" {{ request('order_status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>

                          <div class="col-md-3">
                            <div class="form-group">
                              <label>Delivery Type</label>
                              <select name="delivery_type" class="form-control">
                                <option value="">Select Delivery Type</option>
                                @foreach(['Express','Scheduled'] as $t)
                                  <option value="{{ $t }}" {{ request('delivery_type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>

                        </div>

                        <div class="row p-15">
                          <div class="col-12 mt-25 text-center">
                            <a href="{{ route('admin.all_orders') }}" class="btn btn-rounded btn-warning btn-outline mr-1">Reset</a>
                            <input type="submit" class="btn btn-rounded btn-primary btn-outline" value="Filter">
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="section-content">
                <div class="content-head">
                  <h4 class="content-title">All Orders</h4>
                </div>

                <div class="content-details show">
                  <div class="order-content">
                    <div class="table-responsive">
                      <table id="data-table" class="table data-table table-striped table-bordered">
                        <thead>
                          <tr>
                            <th>S.No.</th>
                            <th>Date</th>
                            <th>Order Id</th>
                            <th>Delivery Type</th>
                            <th>Hub</th>
                            <th>Customer Name</th>
                            <th>Customer Mobile</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse($orders as $key => $v)
                            <tr>
                              <td>{{ $key + 1 }}</td>
                              <td>{{ $v->date_added ? \Carbon\Carbon::parse($v->date_added)->format('d-m-Y') : '-' }}</td>
                              <td>{{ $v->order_id }}</td>
                              <td>{{ $v->delivery_type }}</td>
                              <td>{{ $v->hub }}</td>
                              <td>{{ $v->name }}</td>
                              <td>{{ $v->phone }}</td>
                              <td>
                                <span class="badge
                                  @if($v->order_status == 'Order Pending') badge-secondary
                                  @elseif($v->order_status == 'Order Cancel') badge-danger
                                  @elseif($v->order_status == 'Order Placed') badge-info
                                  @elseif($v->order_status == 'Order Processed') badge-warning
                                  @elseif($v->order_status == 'Order Shipped') badge-primary
                                  @else badge-success
                                  @endif">
                                  {{ $v->order_status }}
                                </span>
                              </td>
                              <td>{{ $v->total_amount }}</td>
                              <td>
                                <a href="{{ url('admin/view_operation_order?order_id='.$v->order_id) }}" class="btn btn-primary btn-sm">View</a>
                              </td>
                            </tr>
                          @empty
                            <tr><td colspan="10" class="text-center">No orders match your filters.</td></tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Narrow hub dropdown to hubs in the selected city.
  (function () {
    var city = document.getElementById('city_id');
    var hub  = document.getElementById('hub_id');
    if (!city || !hub) return;

    function filter() {
      var id = city.value;
      Array.from(hub.options).forEach(function (o) {
        if (!o.value) return;
        var show = !id || o.dataset.city === id;
        o.hidden = !show;
        if (!show && o.selected) { hub.value = ''; }
      });
    }
    city.addEventListener('change', filter);
    filter();
  })();
</script>
@endpush
