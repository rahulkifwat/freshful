@extends('customer_care_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">Scheduled Orders</h4></div>
        <div class="content-details show">
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr><th>Order ID</th><th>Buyer</th><th>Phone</th><th>Total (₹)</th><th>Status</th><th>Date</th><th>View</th></tr>
              </thead>
              <tbody>
                @forelse($orders as $o)
                  <tr>
                    <td>{{ $o->order_id }}</td>
                    <td>{{ $o->buyer_name ?? '—' }}</td>
                    <td>{{ $o->buyer_phone ?? '—' }}</td>
                    <td>{{ number_format($o->total_price, 2) }}</td>
                    <td><span class="badge badge-warning">{{ $o->order_status ?? '—' }}</span></td>
                    <td>{{ $o->created_at ?? '—' }}</td>
                    <td>
                      <a href="{{ route('customer_care_manager.view_order', ['order_id' => $o->order_id]) }}"
                         class="btn btn-info btn-sm"><i class="fa fa-eye"></i></a>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="text-center">No scheduled orders.</td></tr>
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
