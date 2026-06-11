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
                  <h4 class="content-title">Inventory Report</h4>
                </div>

                <div class="content-details show">
                  <form method="get" action="{{ route('admin.inventory_report') }}" class="mb-3">
                    <div class="row">
                      <div class="col-md-4">
                        <select name="city_id" class="form-control" onchange="this.form.submit()">
                          <option value="">All Cities</option>
                          @foreach($cities as $c)
                            <option value="{{ $c->id }}" {{ (string) request('city_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->city }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-4">
                        <select name="hub_id" class="form-control">
                          <option value="">All Hubs</option>
                          @foreach($hubs as $h)
                            <option value="{{ $h->id }}" {{ (string) request('hub_id') === (string) $h->id ? 'selected' : '' }}>{{ $h->hub }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        @if(request()->hasAny(['city_id','hub_id']))
                          <a href="{{ route('admin.inventory_report') }}" class="btn btn-outline-secondary">Reset</a>
                        @endif
                      </div>
                    </div>
                  </form>

                  <div class="table-responsive">
                    <table id="data-table" class="table data-table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>Product ID</th>
                          <th>Product</th>
                          <th>Category</th>
                          <th>UOM</th>
                          <th>Stock</th>
                          <th>Cost</th>
                          <th>MRP</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($rows as $r)
                          <tr>
                            <td>{{ $r->product_id }}</td>
                            <td>{{ $r->product_name }}</td>
                            <td>{{ $r->category ?? '-' }}</td>
                            <td>{{ $r->uom }}</td>
                            <td>{{ $r->stock ?? 0 }}</td>
                            <td>{{ $r->cost ?? '-' }}</td>
                            <td>{{ $r->MRP }}</td>
                          </tr>
                        @empty
                          <tr><td colspan="7" class="text-center">No products.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>
                  <div class="mt-3">{{ $rows->links() }}</div>
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
