@extends('admin.layout.master')

@section('content')
<div class="dashboard-contents">
    <div class="contents-inner">
        <div class="row">
            <div class="full-wdt">
                <div class="contents-inner">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="section-content">

                                <div class="content-head">
                                    <h4 class="content-title">Products</h4>
                                    <a href="{{ route('admin.product_form') }}" class="btn btn-primary btn-sm" style="float:right;">
                                        <i class="fa fa-dot-circle-o"></i> Add Product
                                    </a>
                                </div>

                                <div class="content-details show">
                                    <div class="order-content">

                                        <form class="form-horizontal" method="get" action="{{ route('admin.products') }}">
                                            <div class="row">
                                                <div class="col-md-3" style="margin-bottom: 10px;">
                                                    <select name="main_cat" id="main_category_id" class="form-control">
                                                        <option value="">Select Main Category</option>
                                                        @foreach($main_categories as $mc)
                                                            <option value="{{ $mc->main_category_name }}"
                                                                {{ request('main_cat') === $mc->main_category_name ? 'selected' : '' }}>
                                                                {{ $mc->main_category_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-md-3" style="margin-bottom: 10px;">
                                                    <select name="category" id="category_id" class="form-control">
                                                        <option value="">Select Category</option>
                                                        @foreach($categories as $c)
                                                            <option value="{{ $c->id }}"
                                                                data-parent="{{ $c->main_category_name }}"
                                                                {{ (string) request('category') === (string) $c->id ? 'selected' : '' }}>
                                                                {{ $c->category_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-md-3" style="margin-bottom: 10px;">
                                                    <input type="text" name="search" class="form-control"
                                                           placeholder="Search by product name"
                                                           value="{{ request('search') }}">
                                                </div>

                                                <div class="col-md-3" style="margin-bottom: 10px;">
                                                    <button type="submit" class="btn btn-primary">Search</button>
                                                    @if(request()->hasAny(['main_cat','category','search','product']))
                                                        <a href="{{ route('admin.products') }}" class="btn btn-outline-secondary">Reset</a>
                                                    @endif
                                                </div>
                                            </div>
                                        </form>

                                        <div class="table-responsive">
                                            <table id="data-table" class="table data-table table-striped table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th style="width:60px;"><input type="checkbox" id="selectAll"> All</th>
                                                        <th>Rank</th>
                                                        <th>Product ID</th>
                                                        <th>Name</th>
                                                        <th>Category</th>
                                                        <th>MRP</th>
                                                        <th>Main price</th>
                                                        <th>Image</th>
                                                        <th>Status</th>
                                                        <th>Edit</th>
                                                        <th>Delete</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($products as $p)
                                                        <tr>
                                                            <td><input type="checkbox" name="check" value="{{ $p->id }}"></td>
                                                            <td>{{ $p->ranking }}</td>
                                                            <td>{{ $p->product_id }}</td>
                                                            <td>{{ $p->product_name }}</td>
                                                            <td>{{ $p->category_name ?? '-' }}</td>
                                                            <td>{{ $p->mrp }}</td>
                                                            <td>{{ $p->main_price }}</td>
                                                            <td>
                                                                <img src="{{ asset('uploads/images/products/'.($p->product_image ?: 'no-image.jpg')) }}"
                                                                     width="50" height="50" alt="">
                                                            </td>
                                                            <td>
                                                                <label class="switch">
                                                                    <input type="checkbox" name="status"
                                                                           class="status_enable switch-warning change_status"
                                                                           data-table="products"
                                                                           data-id="{{ $p->id }}"
                                                                           value="{{ $p->status }}"
                                                                           {{ $p->status === 'active' ? 'checked' : '' }}>
                                                                    <span class="slider round"></span>
                                                                </label>
                                                            </td>
                                                            <td>
                                                                <a class="btn btn-sm btn-info" href="{{ route('admin.product_form', ['id' => $p->id]) }}">
                                                                    <i class="fa fa-pencil-square-o"></i>
                                                                </a>
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-danger deleteRecord"
                                                                        data-table="products" data-id="{{ $p->id }}">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="11" class="text-center">No products found.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mt-3 d-flex justify-content-between align-items-center">
                                            <div>Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }}</div>
                                            <div>{{ $products->links() }}</div>
                                        </div>

                                    </div>
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

@push('scripts')
<script>
  // Chain category dropdown to the selected main category.
  (function () {
    var mc = document.getElementById('main_category_id');
    var cat = document.getElementById('category_id');
    if (!mc || !cat) return;

    function filter() {
      var parent = mc.value;
      Array.from(cat.options).forEach(function (o) {
        if (!o.value) return; // keep placeholder
        var show = !parent || o.dataset.parent === parent;
        o.hidden = !show;
        if (!show && o.selected) { cat.value = ''; }
      });
    }
    mc.addEventListener('change', filter);
    filter();
  })();
</script>
@endpush
