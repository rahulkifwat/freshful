@extends('customer_care_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">Inward / Outward</h4></div>
        <div class="content-details show">
          @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty())
            <div class="alert alert-info">No inventory data found.</div>
          @else
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr><th>ID</th><th>Product</th><th>Hub</th><th>Stock</th><th>Live Stock</th><th>Fresh Stock</th><th>Variance</th></tr>
              </thead>
              <tbody>
                @forelse($rows as $r)
                  @php $variance = (($r->live_stock ?? 0) + ($r->fresh_stock ?? 0)) - ($r->stock ?? 0); @endphp
                  <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->product_name ?? '—' }}</td>
                    <td>{{ $r->hub_name ?? '—' }}</td>
                    <td>{{ $r->stock ?? '—' }}</td>
                    <td>{{ $r->live_stock ?? '—' }}</td>
                    <td>{{ $r->fresh_stock ?? '—' }}</td>
                    <td class="{{ $variance < 0 ? 'text-danger' : 'text-success' }}">{{ $variance }}</td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="text-center">No records found.</td></tr>
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
