@extends('planning_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">All Customers</h4></div>
        <div class="content-details show">
          <form method="get" class="mb-3">
            <div class="input-group" style="max-width:400px">
              <input type="text" name="search" class="form-control"
                     placeholder="Search name / phone / email" value="{{ $search }}">
              <div class="input-group-append">
                <button class="btn btn-primary" type="submit">Search</button>
              </div>
            </div>
          </form>
          <div class="table-responsive">
            <table class="table table-striped table-bordered">
              <thead>
                <tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Status</th><th>Registered</th></tr>
              </thead>
              <tbody>
                @forelse($customers as $c)
                  <tr>
                    <td>{{ $c->id }}</td>
                    <td>{{ $c->name ?? '—' }}</td>
                    <td>{{ $c->phone ?? '—' }}</td>
                    <td>{{ $c->email ?? '—' }}</td>
                    <td><span class="badge badge-{{ ($c->status ?? 'active') === 'active' ? 'success' : 'secondary' }}">{{ $c->status ?? 'active' }}</span></td>
                    <td>{{ $c->created_at ?? '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center">No customers found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mt-3">{{ $customers->links() }}</div>
        </div>
      </div>
    </div></div></div></div></div>
  </div>
</div>
@endsection
