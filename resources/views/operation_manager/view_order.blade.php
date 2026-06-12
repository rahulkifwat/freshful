@extends('operation_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head">
          <h4 class="content-title">Order Detail — {{ $order_id }}</h4>
          <a href="{{ route('operation_manager.all_orders') }}" class="btn btn-secondary btn-sm">← Back</a>
        </div>
        <div class="content-details show">

          @if($buyer)
          <div class="card mb-4">
            <div class="card-header"><strong>Buyer Info</strong></div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-3"><strong>Name:</strong> {{ $buyer->name ?? '—' }}</div>
                <div class="col-md-3"><strong>Phone:</strong> {{ $buyer->phone ?? '—' }}</div>
                <div class="col-md-3"><strong>Email:</strong> {{ $buyer->email ?? '—' }}</div>
              </div>
            </div>
          </div>
          @endif

          <div class="table-responsive">
            <table class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>ID</th><th>Product</th><th>Qty</th><th>Unit Price (₹)</th>
                  <th>Total (₹)</th><th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse($items as $item)
                  <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->product_name ?? ($item->product_id ?? '—') }}</td>
                    <td>{{ $item->quantity ?? '—' }}</td>
                    <td>{{ isset($item->price) ? number_format($item->price, 2) : '—' }}</td>
                    <td>{{ isset($item->total_price) ? number_format($item->total_price, 2) : '—' }}</td>
                    <td>{{ $item->order_status ?? '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center">No items found for this order.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div></div></div></div></div>
  </div>
</div>
@endsection
