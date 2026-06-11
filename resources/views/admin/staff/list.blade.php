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
                  <h4 class="content-title">{{ $cfg['label'] }}s</h4>
                  <a href="{{ url('admin/'.$cfg['add_url']) }}" class="btn btn-primary btn-sm" style="float:right;">
                    <i class="fa fa-dot-circle-o"></i> Add {{ $cfg['label'] }}
                  </a>
                </div>

                <div class="content-details show">

                  @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                  @endif
                  @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                  @endif

                  <form method="get" action="{{ url('admin/'.$cfg['list_url']) }}" class="mb-3">
                    <div class="row">
                      <div class="col-md-4">
                        <input type="text" name="search" class="form-control"
                               placeholder="Search by name, email or phone"
                               value="{{ request('search') }}">
                      </div>
                      <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if(request('search'))
                          <a href="{{ url('admin/'.$cfg['list_url']) }}" class="btn btn-outline-secondary">Reset</a>
                        @endif
                      </div>
                    </div>
                  </form>

                  <div class="table-responsive">
                    <table id="data-table" class="table data-table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Email</th>
                          <th>Phone</th>
                          @if($cfg['table'] === 'hub_user')<th>HUB</th>@endif
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($rows as $r)
                          <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->name }}</td>
                            <td>{{ $r->email }}</td>
                            <td>{{ $r->phone ?? '-' }}</td>
                            @if($cfg['table'] === 'hub_user')<td>{{ $r->hub_name ?? '-' }}</td>@endif
                            <td>
                              <label class="switch">
                                <input type="checkbox" name="status"
                                       class="status_enable switch-warning change_status"
                                       data-table="{{ $cfg['table'] }}"
                                       data-id="{{ $r->id }}"
                                       value="{{ $r->status ?? 'active' }}"
                                       {{ ($r->status ?? 'active') === 'active' ? 'checked' : '' }}>
                                <span class="slider round"></span>
                              </label>
                            </td>
                            <td>
                              <a href="{{ url('admin/'.$cfg['view_url'].'?id='.$r->id) }}" class="btn btn-sm btn-info">View</a>
                              <button type="button"
                                      data-table="{{ $cfg['table'] }}"
                                      data-id="{{ $r->id }}"
                                      class="btn btn-danger btn-sm deleteRecord">
                                <i class="fa fa-trash"></i> Delete
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="{{ $cfg['table'] === 'hub_user' ? 7 : 6 }}" class="text-center">No records found.</td></tr>
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
