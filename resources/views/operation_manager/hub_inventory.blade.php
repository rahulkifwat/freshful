@extends('operation_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">Hub Inventory</h4></div>
        <div class="content-details show">
          @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty())
            <div class="alert alert-info">No inventory table found or no records yet.</div>
          @else
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr><th>ID</th><th>Product</th><th>Hub</th><th>Stock</th><th>Live Stock</th><th>Fresh Stock</th></tr>
              </thead>
              <tbody>
                @forelse($rows as $r)
                  <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->product_name ?? '—' }}</td>
                    <td>{{ $r->hub_name ?? '—' }}</td>
                    <td>{{ $r->stock ?? '—' }}</td>
                    <td>{{ $r->live_stock ?? '—' }}</td>
                    <td>{{ $r->fresh_stock ?? '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center">No inventory records found.</td></tr>
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
