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
                  <h4 class="content-title">Buyers</h4>
                </div>

                <div class="content-details show">

                  <form method="get" action="{{ route('admin.buyers') }}" class="mb-3">
                    <div class="row">
                      <div class="col-md-4">
                        <input type="text" name="search" class="form-control"
                               placeholder="Search by name, email or phone"
                               value="{{ request('search') }}">
                      </div>
                      <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if(request('search'))
                          <a href="{{ route('admin.buyers') }}" class="btn btn-outline-secondary">Reset</a>
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
                          <th>Orders</th>
                          <th>Wallet</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($buyers as $v)
                          <tr>
                            <td>{{ $v->id }}</td>
                            <td>{{ $v->name }}</td>
                            <td>{{ $v->email }}</td>
                            <td>{{ $v->phone ?? '-' }}</td>
                            <td><a href="{{ url('admin/order?buyer_id='.$v->id) }}">Check order</a></td>
                            <td>{{ $v->wallet_amount ?? 0 }}</td>
                            <td>
                              <label class="switch">
                                <input type="checkbox" name="status"
                                       class="status_enable switch-warning change_status"
                                       data-table="buyers"
                                       data-id="{{ $v->id }}"
                                       value="{{ $v->status }}"
                                       {{ $v->status === 'active' ? 'checked' : '' }}>
                                <span class="slider round"></span>
                              </label>
                            </td>
                            <td>
                              <a href="{{ url('admin/view_buyer?id='.$v->id) }}">
                                <button class="btn btn-outline-primary btn-sm">View</button>
                              </a>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="8" class="text-center">No buyers found.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>

                  <div class="mt-3">
                    {{ $buyers->links() }}
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
