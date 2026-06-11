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
                  <h4 class="content-title">Product Units</h4>
                </div>

                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

                <div class="content-details show">

                  {{-- Add unit inline form --}}
                  <div class="card pay-invoice mb-4">
                    <div class="card-body">
                      <form method="post" action="{{ route('admin.product_unit_store') }}">
                        @csrf
                        @if($errors->any())
                          <div class="alert alert-danger mb-2"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                        @endif
                        <div class="row">
                          <div class="col-md-6">
                            <input type="text" name="unit" class="form-control" placeholder="Unit name (e.g. kg, g, pcs)"
                                   value="{{ old('unit') }}" required>
                          </div>
                          <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Add Unit</button>
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
                          <th>Unit</th>
                          <th>Status</th>
                          <th>Delete</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($rows as $r)
                          <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->unit }}</td>
                            <td>
                              <label class="switch">
                                <input type="checkbox" class="status_enable switch-warning change_status"
                                       data-table="product_units" data-id="{{ $r->id }}"
                                       value="{{ $r->status ?? 'active' }}"
                                       {{ ($r->status ?? 'active') === 'active' ? 'checked' : '' }}>
                                <span class="slider round"></span>
                              </label>
                            </td>
                            <td>
                              <button type="button" data-table="product_units" data-id="{{ $r->id }}"
                                      class="btn btn-danger btn-sm deleteRecord">
                                <i class="fa fa-trash"></i>
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="4" class="text-center">No units found.</td></tr>
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
