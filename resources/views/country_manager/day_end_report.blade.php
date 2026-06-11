@extends('country_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">Day-End Report</h4></div>
        <div class="content-details show">

          <form method="get" class="mb-4">
            <div class="row align-items-end">
              <div class="col-md-3 mb-2">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}">
              </div>
              <div class="col-md-2 mb-2">
                <button class="btn btn-primary" type="submit">View</button>
              </div>
            </div>
          </form>

          @if($summary)
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="card border-left-success shadow py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Orders</div>
                  <div class="h5 mb-0 font-weight-bold">{{ number_format($summary->total_orders) }}</div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card border-left-primary shadow py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Revenue</div>
                  <div class="h5 mb-0 font-weight-bold">₹ {{ number_format($summary->total_revenue, 2) }}</div>
                </div>
              </div>
            </div>
          </div>
          @endif

          @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty())
            <div class="alert alert-info">No orders data available.</div>
          @else
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr><th>Order ID</th><th>Buyer</th><th>Total (₹)</th><th>Status</th><th>Type</th><th>View</th></tr>
              </thead>
              <tbody>
                @forelse($rows as $o)
                  <tr>
                    <td>{{ $o->order_id }}</td>
                    <td>{{ $o->buyer_name ?? '—' }}</td>
                    <td>{{ number_format($o->total_price, 2) }}</td>
                    <td><span class="badge badge-{{ str_contains($o->order_status ?? '', 'Delivered') ? 'success' : (str_contains($o->order_status ?? '', 'Cancel') ? 'danger' : 'warning') }}">{{ $o->order_status ?? '—' }}</span></td>
                    <td>{{ $o->delivery_type ?? '—' }}</td>
                    <td><a href="{{ route('country_manager.view_order', ['order_id' => $o->order_id]) }}" class="btn btn-info btn-sm"><i class="fa fa-eye"></i></a></td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center">No orders for this date.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if(method_exists($rows, 'links'))<div class="mt-3">{{ $rows->links() }}</div>@endif
          @endif

        </div>
      </div>
    </div></div></div></div></div>
  </div>
</div>
@endsection
