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
                  <h4 class="content-title">Product Filters</h4>
                </div>

                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

                <div class="content-details show">

                  {{-- Add / Edit filter form --}}
                  <div class="card pay-invoice mb-4">
                    <div class="card-body">
                      <h6 class="mb-3">{{ request('edit_id') ? 'Edit Filter' : 'Add Filter' }}</h6>
                      <form method="post" action="{{ route('admin.product_filter_store') }}">
                        @csrf
                        @if(request('edit_id'))
                          @php $editing = $rows->firstWhere('id', (int) request('edit_id')); @endphp
                          @if($editing)<input type="hidden" name="id" value="{{ $editing->id }}">@endif
                        @endif
                        @if($errors->any())
                          <div class="alert alert-danger mb-2"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                        @endif
                        <div class="row">
                          <div class="col-md-3 mb-2">
                            <select name="main_cat_id" class="form-control">
                              <option value="">Main Category</option>
                              @foreach($main_categories as $mc)
                                <option value="{{ $mc->id }}"
                                  {{ old('main_cat_id', $editing->main_cat_id ?? '') == $mc->id ? 'selected' : '' }}>
                                  {{ $mc->name }}
                                </option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-md-3 mb-2">
                            <select name="subcat_id" class="form-control">
                              <option value="">Sub Category</option>
                              @foreach($sub_categories as $sc)
                                <option value="{{ $sc->id }}"
                                  {{ old('subcat_id', $editing->subcat_id ?? '') == $sc->id ? 'selected' : '' }}>
                                  {{ $sc->name }}
                                </option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-md-2 mb-2">
                            <input type="text" name="property_name" class="form-control" placeholder="Property Name"
                                   value="{{ old('property_name', $editing->property_name ?? '') }}" required>
                          </div>
                          <div class="col-md-2 mb-2">
                            <input type="text" name="parameter" class="form-control" placeholder="Parameter"
                                   value="{{ old('parameter', $editing->parameter ?? '') }}">
                          </div>
                          <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-primary">
                              {{ request('edit_id') ? 'Update' : 'Add' }}
                            </button>
                          </div>
                        </div>
                      </form>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table data-table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Main Category</th>
                          <th>Sub Category</th>
                          <th>Property Name</th>
                          <th>Parameter</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($rows as $r)
                          <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->main_cat_name ?? '—' }}</td>
                            <td>{{ $r->sub_cat_name ?? '—' }}</td>
                            <td>{{ $r->property_name }}</td>
                            <td>{{ $r->parameter ?? '—' }}</td>
                            <td>
                              <a href="{{ route('admin.product_filters', ['edit_id' => $r->id]) }}"
                                 class="btn btn-info btn-sm">
                                <i class="fa fa-pencil-square-o"></i>
                              </a>
                              <button type="button" data-table="product_filter" data-id="{{ $r->id }}"
                                      class="btn btn-danger btn-sm deleteRecord">
                                <i class="fa fa-trash"></i>
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="6" class="text-center">No filters found.</td></tr>
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
