@extends('marketing_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">Products</h4></div>
        <div class="content-details show">
          <form method="get" class="mb-3">
            <div class="input-group" style="max-width:400px">
              <input type="text" name="search" class="form-control"
                     placeholder="Search product name" value="{{ $search }}">
              <div class="input-group-append">
                <button class="btn btn-primary" type="submit">Search</button>
              </div>
            </div>
          </form>
          <div class="table-responsive">
            <table class="table table-striped table-bordered">
              <thead>
                <tr><th>ID</th><th>Image</th><th>Product Name</th><th>Category</th><th>Price (₹)</th><th>Status</th></tr>
              </thead>
              <tbody>
                @forelse($products as $p)
                  <tr>
                    <td>{{ $p->id }}</td>
                    <td>
                      @if(!empty($p->image))
                        <img src="{{ asset('uploads/images/products/'.$p->image) }}"
                             style="width:45px;height:45px;object-fit:cover" alt="">
                      @else —
                      @endif
                    </td>
                    <td>{{ $p->product_name ?? '—' }}</td>
                    <td>{{ $p->cat_name ?? '—' }}</td>
                    <td>{{ $p->main_price ?? '—' }}</td>
                    <td>
                      <span class="badge badge-{{ ($p->status ?? 'active') === 'active' ? 'success' : 'secondary' }}">
                        {{ $p->status ?? 'active' }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center">No products found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mt-3">{{ $products->links() }}</div>
        </div>
      </div>
    </div></div></div></div></div>
  </div>
</div>
@endsection
