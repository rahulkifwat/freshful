@extends('account_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">

        <div class="content-head">
          <h4 class="content-title">Sales Report</h4>
        </div>

        <div class="content-details show">

          <form method="get" class="mb-4">
            <div class="row align-items-end">
              <div class="col-md-3 mb-2">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control" value="{{ $from ?? '' }}">
              </div>
              <div class="col-md-3 mb-2">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control" value="{{ $to ?? '' }}">
              </div>
              <div class="col-md-2 mb-2">
                <button class="btn btn-primary" type="submit">Generate</button>
              </div>
            </div>
          </form>

          @if($totals)
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="card border-left-success shadow py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Orders</div>
                  <div class="h5 mb-0 font-weight-bold">{{ number_format($totals->total_orders) }}</div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card border-left-primary shadow py-2">
                <div class="card-body">
                  <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Revenue</div>
                  <div class="h5 mb-0 font-weight-bold">₹ {{ number_format($totals->total_revenue, 2) }}</div>
                </div>
              </div>
            </div>
          </div>
          @endif

          @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty())
            <div class="alert alert-info">No orders table found.</div>
          @else
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Total Orders</th>
                  <th>Total Revenue (₹)</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $r)
                  <tr>
                    <td>{{ $r->sale_date }}</td>
                    <td>{{ number_format($r->total_orders) }}</td>
                    <td>{{ number_format($r->total_revenue, 2) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="text-center">No sales data for the selected period.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if(method_exists($rows, 'links'))
            <div class="mt-3">{{ $rows->links() }}</div>
          @endif
          @endif

        </div>
      </div>
    </div></div></div></div></div>
  </div>
</div>
@endsection
