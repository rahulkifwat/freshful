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
                  <h4 class="content-title">Hubs</h4>
                  <a href="{{ route('admin.hub_form') }}" class="btn btn-primary btn-sm" style="float:right;">
                    <i class="fa fa-dot-circle-o"></i> Add Hub
                  </a>
                </div>

                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

                <div class="content-details show">
                  <form method="get" action="{{ route('admin.hub') }}" class="mb-3">
                    <div class="row">
                      <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search hub" value="{{ request('search') }}">
                      </div>
                      <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if(request('search'))<a href="{{ route('admin.hub') }}" class="btn btn-outline-secondary">Reset</a>@endif
                      </div>
                    </div>
                  </form>

                  <div class="table-responsive">
                    <table id="data-table" class="table data-table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Hub</th>
                          <th>City</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($hubs as $h)
                          <tr>
                            <td>{{ $h->id }}</td>
                            <td>{{ $h->hub }}</td>
                            <td>{{ $h->city_name ?? '-' }}</td>
                            <td>
                              <label class="switch">
                                <input type="checkbox" name="status"
                                       class="status_enable switch-warning change_status"
                                       data-table="hubs"
                                       data-id="{{ $h->id }}"
                                       value="{{ $h->status ?? 'active' }}"
                                       {{ ($h->status ?? 'active') === 'active' ? 'checked' : '' }}>
                                <span class="slider round"></span>
                              </label>
                            </td>
                            <td>
                              <a href="{{ route('admin.hub_form', ['id' => $h->id]) }}" class="btn btn-info btn-sm">
                                <i class="fa fa-pencil-square-o"></i>
                              </a>
                              <button type="button"
                                      data-table="hubs"
                                      data-id="{{ $h->id }}"
                                      class="btn btn-danger btn-sm deleteRecord">
                                <i class="fa fa-trash"></i> Delete
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="5" class="text-center">No hubs found.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>

                  <div class="mt-3">{{ $hubs->links() }}</div>

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
