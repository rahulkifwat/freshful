@extends('account_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">

        <div class="content-head">
          <h4 class="content-title text-danger">Danger Stock <small class="text-muted">(≤ 10 units)</small></h4>
        </div>

        <div class="content-details show">
          @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty())
            <div class="alert alert-success">No danger-stock products found.</div>
          @else
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Image</th>
                  <th>Product Name</th>
                  <th>Price (₹)</th>
                  <th>Stock</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $p)
                  <tr class="table-danger">
                    <td>{{ $p->id }}</td>
                    <td>
                      @if(!empty($p->image))
                        <img src="{{ asset('uploads/images/products/'.$p->image) }}"
                             style="width:40px;height:40px;object-fit:cover" alt="">
                      @else —
                      @endif
                    </td>
                    <td>{{ $p->product_name ?? '—' }}</td>
                    <td>{{ $p->main_price ?? '—' }}</td>
                    <td>
                      <span class="badge badge-danger">
                        {{ $p->stock_qty ?? ($p->quantity ?? '?') }}
                      </span>
                    </td>
                    <td>{{ $p->status ?? '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center">No danger stock products.</td></tr>
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
