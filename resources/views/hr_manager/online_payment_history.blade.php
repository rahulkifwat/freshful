@extends('hr_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">Online Payment History</h4></div>
        <div class="content-details show">
          @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty())
            <div class="alert alert-info">No online payment records found.</div>
          @else
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr><th>Order ID</th><th>Buyer</th><th>Payment Type</th><th>Total (₹)</th><th>Date</th></tr>
              </thead>
              <tbody>
                @forelse($rows as $r)
                  <tr>
                    <td>{{ $r->order_id }}</td>
                    <td>{{ $r->buyer_name ?? '—' }}</td>
                    <td>{{ $r->payment_type ?? '—' }}</td>
                    <td>{{ number_format($r->total_price, 2) }}</td>
                    <td>{{ $r->created_at ?? '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center">No online payment records.</td></tr>
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
