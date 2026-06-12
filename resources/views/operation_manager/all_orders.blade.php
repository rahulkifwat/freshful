@extends('operation_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">All Orders</h4></div>
        <div class="content-details show">
          <form method="get" class="mb-3">
            <div class="row">
              <div class="col-md-4 mb-2">
                <input type="text" name="search" class="form-control"
                       placeholder="Order ID / buyer name / phone" value="{{ $search ?? '' }}">
              </div>
              <div class="col-md-3 mb-2">
                <select name="status" class="form-control">
                  <option value="">All Statuses</option>
                  @foreach(['Order Pending','Order Placed','Order Processed','Order Shipped','Order Delivered','Order Cancel'] as $s)
                    <option value="{{ $s }}" {{ ($status ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2 mb-2">
                <button class="btn btn-primary" type="submit">Filter</button>
              </div>
            </div>
          </form>
          <div class="table-responsive">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>Order ID</th><th>Buyer</th><th>Phone</th><th>Total (₹)</th>
                  <th>Status</th><th>Date</th><th>Change Status</th><th>View</th>
                </tr>
              </thead>
              <tbody>
                @forelse($orders as $o)
                  <tr>
                    <td>{{ $o->order_id }}</td>
                    <td>{{ $o->buyer_name ?? '—' }}</td>
                    <td>{{ $o->buyer_phone ?? '—' }}</td>
                    <td>{{ number_format($o->total_price, 2) }}</td>
                    <td>
                      <span class="badge badge-{{ str_contains($o->order_status ?? '', 'Delivered') ? 'success' : (str_contains($o->order_status ?? '', 'Cancel') ? 'danger' : 'warning') }}">
                        {{ $o->order_status ?? '—' }}
                      </span>
                    </td>
                    <td>{{ $o->created_at ?? '—' }}</td>
                    <td>
                      <select class="form-control form-control-sm change_status_order"
                              data-id="{{ $o->id }}" data-order_id="{{ $o->order_id }}"
                              style="min-width:140px">
                        @foreach(['Order Pending','Order Placed','Order Processed','Order Shipped','Order Delivered','Order Cancel'] as $s)
                          <option value="{{ $s }}" {{ $o->order_status === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                      </select>
                    </td>
                    <td>
                      <a href="{{ route('operation_manager.view_order', ['order_id' => $o->order_id]) }}"
                         class="btn btn-info btn-sm"><i class="fa fa-eye"></i></a>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="8" class="text-center">No orders found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mt-3">{{ $orders->links() }}</div>
        </div>
      </div>
    </div></div></div></div></div>
  </div>
</div>
@endsection
