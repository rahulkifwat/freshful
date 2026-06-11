@extends('area_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">Wallet History</h4></div>
        <div class="content-details show">
          @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty())
            <div class="alert alert-info">No wallet history found.</div>
          @else
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr><th>ID</th><th>Buyer ID</th><th>Amount (₹)</th><th>Type</th><th>Description</th><th>Date</th></tr>
              </thead>
              <tbody>
                @forelse($rows as $r)
                  <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->buyer_id ?? ($r->user_id ?? '—') }}</td>
                    <td>{{ isset($r->amount) ? number_format($r->amount, 2) : '—' }}</td>
                    <td>{{ $r->type ?? ($r->transaction_type ?? '—') }}</td>
                    <td>{{ $r->description ?? ($r->note ?? '—') }}</td>
                    <td>{{ $r->created_at ?? '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center">No wallet records found.</td></tr>
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
