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
                  <h4 class="content-title">Cities</h4>
                  <a href="{{ route('admin.city_form') }}" class="btn btn-primary btn-sm" style="float:right;">
                    <i class="fa fa-dot-circle-o"></i> Add City
                  </a>
                </div>

                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

                <div class="content-details show">
                  <form method="get" action="{{ route('admin.city') }}" class="mb-3">
                    <div class="row">
                      <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search city" value="{{ request('search') }}">
                      </div>
                      <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if(request('search'))<a href="{{ route('admin.city') }}" class="btn btn-outline-secondary">Reset</a>@endif
                      </div>
                    </div>
                  </form>

                  <div class="table-responsive">
                    <table id="data-table" class="table data-table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>City</th>
                          <th>State</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($cities as $c)
                          <tr>
                            <td>{{ $c->id }}</td>
                            <td>{{ $c->city }}</td>
                            <td>{{ $c->state_name ?? '-' }}</td>
                            <td>
                              <label class="switch">
                                <input type="checkbox" name="status"
                                       class="status_enable switch-warning change_status"
                                       data-table="cities"
                                       data-id="{{ $c->id }}"
                                       value="{{ $c->status ?? 'active' }}"
                                       {{ ($c->status ?? 'active') === 'active' ? 'checked' : '' }}>
                                <span class="slider round"></span>
                              </label>
                            </td>
                            <td>
                              <a href="{{ route('admin.city_form', ['id' => $c->id]) }}" class="btn btn-info btn-sm">
                                <i class="fa fa-pencil-square-o"></i>
                              </a>
                              <button type="button"
                                      data-table="cities"
                                      data-id="{{ $c->id }}"
                                      class="btn btn-danger btn-sm deleteRecord">
                                <i class="fa fa-trash"></i> Delete
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="5" class="text-center">No cities found.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>

                  <div class="mt-3">{{ $cities->links() }}</div>

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
