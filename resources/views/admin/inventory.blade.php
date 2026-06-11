@extends('admin.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="contents-inner">
          <div class="row">
            <div class="col-12">
              <div class="section-content">

                <div class="content-head">
                  <h4 class="content-title">Inventory</h4>
                </div>

                <div class="content-details show">
                  <form method="get" action="{{ route('admin.inventory') }}" class="mb-3">
                    <div class="row">
                      <div class="col-md-4">
                        <input type="text" name="search" class="form-control"
                               placeholder="Search by product name or product ID"
                               value="{{ request('search') }}">
                      </div>
                      <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if(request('search'))<a href="{{ route('admin.inventory') }}" class="btn btn-outline-secondary">Reset</a>@endif
                      </div>
                    </div>
                  </form>

                  <div class="table-responsive">
                    <table id="data-table" class="table data-table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>Image</th>
                          <th>Product ID</th>
                          <th>Name</th>
                          <th>Category</th>
                          <th>UOM</th>
                          <th>Stock</th>
                          <th>Cost</th>
                          <th>Main</th>
                          <th>MRP</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($products as $p)
                          <tr>
                            <td><img src="{{ asset('uploads/images/products/'.($p->product_image ?: 'no-image.jpg')) }}" width="50" height="50" alt=""></td>
                            <td>{{ $p->product_id }}</td>
                            <td>{{ $p->product_name }}</td>
                            <td>{{ $p->category_name ?? '-' }}</td>
                            <td>{{ trim(($p->unit_quantity ?? '').' '.($p->product_unit ?? '')) ?: '-' }}</td>
                            <td>{{ $p->stock ?? 0 }}</td>
                            <td>{{ $p->cost_price ?? '-' }}</td>
                            <td>{{ $p->main_price }}</td>
                            <td>{{ $p->mrp }}</td>
                            <td>
                              <label class="switch">
                                <input type="checkbox" class="status_enable switch-warning change_status"
                                       data-table="products"
                                       data-id="{{ $p->id }}"
                                       value="{{ $p->status ?? 'active' }}"
                                       {{ ($p->status ?? 'active') === 'active' ? 'checked' : '' }}>
                                <span class="slider round"></span>
                              </label>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="10" class="text-center">No products.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>
                  <div class="mt-3">{{ $products->links() }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
