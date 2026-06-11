@extends('admin.layout.master')

@section('content')
@php
  $tagSel  = old('tag', !empty($row->tag) ? explode(',', $row->tag) : []);
  $propSel = old('property', !empty($row->property) ? explode(',', $row->property) : ['']);
  if (empty($propSel)) { $propSel = ['']; }
@endphp

<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="col-md-12">
          <div class="section-content">

            <div class="content-head">
              <h4 class="content-title">{{ $row ? 'Edit Product' : 'Add Product' }}</h4>
              <a href="{{ route('admin.products') }}" class="btn btn-secondary btn-sm" style="float:right;">
                <i class="fa fa-arrow-left"></i> Back
              </a>
            </div>

            <div class="content-details show">
              @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
              @endif

              <div class="card pay-invoice">
                <div class="card-body">
                  <form method="post" action="{{ route('admin.product_store') }}" enctype="multipart/form-data">
                    @csrf
                    @if($row)<input type="hidden" name="id" value="{{ $row->id }}">@endif

                    @if($row)
                      <div class="row form-group">
                        <div class="col col-md-3"><label>Product ID</label></div>
                        <div class="col-12 col-md-6">
                          <input type="text" class="form-control" value="{{ $row->product_id }}" readonly>
                        </div>
                      </div>
                    @endif

                    @php
                      // Pre-fill main category from the selected sub-category's group_type
                      // when editing an existing product.
                      $currentMain = old('main_category_id');
                      if (!$currentMain && $row && !empty($row->category_id)) {
                          $currentMain = $categories->firstWhere('id', $row->category_id)->main_category_name ?? '';
                      }
                    @endphp

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Main Category</label></div>
                      <div class="col-12 col-md-6">
                        <select id="main_category_id" name="main_category_id" class="form-control">
                          <option value="">Select Main Category</option>
                          @foreach($main_categories as $mc)
                            <option value="{{ $mc->main_category_name }}"
                              {{ $currentMain === $mc->main_category_name ? 'selected' : '' }}>
                              {{ $mc->main_category_name }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Category</label></div>
                      <div class="col-12 col-md-6">
                        <select id="category_id" name="category_id" class="form-control">
                          <option value="">Select Category</option>
                          @foreach($categories as $c)
                            <option value="{{ $c->id }}"
                              data-parent="{{ $c->main_category_name }}"
                              {{ (string) old('category_id', $row->category_id ?? '') === (string) $c->id ? 'selected' : '' }}>
                              {{ $c->category_name }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Item <span class="text-danger">*</span></label></div>
                      <div class="col-12 col-md-6">
                        <select name="item_id" class="form-control" required>
                          <option value="">Select Item</option>
                          @foreach($items as $it)
                            <option value="{{ $it->id }}"
                              {{ (string) old('item_id', $row->item_id ?? '') === (string) $it->id ? 'selected' : '' }}>
                              {{ $it->item }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>

                    @if($tags->count())
                      <div class="row form-group">
                        <div class="col col-md-3"><label>Tags</label></div>
                        <div class="col-12 col-md-6">
                          <select name="tag[]" class="form-control" multiple size="4">
                            @foreach($tags as $t)
                              <option value="{{ $t->tag }}" {{ in_array($t->tag, $tagSel ?: []) ? 'selected' : '' }}>{{ $t->tag }}</option>
                            @endforeach
                          </select>
                          <small class="text-muted">Hold Ctrl/Cmd to select multiple.</small>
                        </div>
                      </div>
                    @endif

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Product Unit</label></div>
                      <div class="col-12 col-md-6">
                        <select name="product_unit" class="form-control">
                          <option value="">Select Unit</option>
                          @foreach($units as $u)
                            <option value="{{ $u->unit }}"
                              {{ old('product_unit', $row->product_unit ?? '') === $u->unit ? 'selected' : '' }}>
                              {{ $u->unit }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Unit Quantity</label></div>
                      <div class="col-12 col-md-6">
                        <input type="number" step="0.01" name="unit_quantity" class="form-control"
                               value="{{ old('unit_quantity', $row->unit_quantity ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Gross Quantity</label></div>
                      <div class="col-12 col-md-6">
                        <input type="number" step="0.01" name="gross_quantity" class="form-control"
                               value="{{ old('gross_quantity', $row->gross_quantity ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>MRP <span class="text-danger">*</span></label></div>
                      <div class="col-12 col-md-6">
                        <input type="number" step="0.01" name="MRP" class="form-control" required
                               value="{{ old('MRP', $row->MRP ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Main Price <span class="text-danger">*</span></label></div>
                      <div class="col-12 col-md-6">
                        <input type="number" step="0.01" name="main_price" class="form-control" required
                               value="{{ old('main_price', $row->main_price ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Cost Price</label></div>
                      <div class="col-12 col-md-6">
                        <input type="number" step="0.01" name="cost_price" class="form-control"
                               value="{{ old('cost_price', $row->cost_price ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Stock</label></div>
                      <div class="col-12 col-md-6">
                        <input type="number" name="stock" class="form-control"
                               value="{{ old('stock', $row->stock ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Pieces</label></div>
                      <div class="col-12 col-md-6">
                        <input type="text" name="pieces" class="form-control"
                               value="{{ old('pieces', $row->pieces ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Serves</label></div>
                      <div class="col-12 col-md-6">
                        <input type="text" name="serves" class="form-control"
                               value="{{ old('serves', $row->serves ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Cooking Time</label></div>
                      <div class="col-12 col-md-6">
                        <input type="text" name="cooking_time" class="form-control" placeholder="e.g. 30 min"
                               value="{{ old('cooking_time', $row->cooking_time ?? '') }}">
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Properties</label></div>
                      <div class="col-12 col-md-6" id="property_wrap">
                        @foreach($propSel as $i => $val)
                          <div class="d-flex mb-1">
                            <input type="text" name="property[]" class="form-control" value="{{ $val }}" placeholder="e.g. Organic">
                            <button type="button" class="btn btn-outline-danger ml-2 property_remove">×</button>
                          </div>
                        @endforeach
                        <button type="button" id="property_add" class="btn btn-sm btn-outline-primary mt-1">+ Add property</button>
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Description</label></div>
                      <div class="col-12 col-md-6">
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $row->description ?? '') }}</textarea>
                      </div>
                    </div>

                    <div class="row form-group">
                      <div class="col col-md-3"><label>Product Image</label></div>
                      <div class="col-12 col-md-6">
                        <input type="file" name="product_image" class="form-control" accept="image/*">
                        @if($row && !empty($row->product_image))
                          <div class="mt-2">
                            <img src="{{ asset('uploads/images/products/'.$row->product_image) }}" height="80" alt="">
                            <small class="text-muted d-block">Leave empty to keep current image.</small>
                          </div>
                        @endif
                      </div>
                    </div>

                    <div class="card-footer">
                      <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-dot-circle-o"></i> {{ $row ? 'Update' : 'Add' }} Product
                      </button>
                      <a href="{{ route('admin.products') }}" class="btn btn-secondary btn-sm">Cancel</a>
                    </div>

                  </form>
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
  // Chain category dropdown to main category.
  (function () {
    var mc  = document.getElementById('main_category_id');
    var cat = document.getElementById('category_id');
    if (!mc || !cat) return;
    function filter() {
      var p = mc.value;
      Array.from(cat.options).forEach(function (o) {
        if (!o.value) return;
        var show = !p || o.dataset.parent === p;
        o.hidden = !show;
        if (!show && o.selected) cat.value = '';
      });
    }
    mc.addEventListener('change', filter);
    filter();
  })();

  // Add/remove property rows.
  (function () {
    var wrap = document.getElementById('property_wrap');
    var add  = document.getElementById('property_add');
    if (!wrap || !add) return;

    function row() {
      var d = document.createElement('div');
      d.className = 'd-flex mb-1';
      d.innerHTML =
        '<input type="text" name="property[]" class="form-control" placeholder="e.g. Organic">' +
        '<button type="button" class="btn btn-outline-danger ml-2 property_remove">×</button>';
      return d;
    }
    add.addEventListener('click', function () { wrap.insertBefore(row(), add); });
    wrap.addEventListener('click', function (e) {
      if (e.target && e.target.classList.contains('property_remove')) {
        var p = e.target.closest('.d-flex');
        if (p) p.remove();
      }
    });
  })();
</script>
@endpush
