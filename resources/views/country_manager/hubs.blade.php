@extends('country_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">Hubs</h4></div>
        <div class="content-details show">
          @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty())
            <div class="alert alert-info">No hubs table found or no records yet.</div>
          @else
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr><th>ID</th><th>Hub Name</th><th>City</th><th>Address</th><th>Status</th></tr>
              </thead>
              <tbody>
                @forelse($rows as $r)
                  <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->hub ?? '—' }}</td>
                    <td>{{ $r->city_name ?? '—' }}</td>
                    <td>{{ $r->address ?? '—' }}</td>
                    <td><span class="badge badge-{{ ($r->status ?? 'active') === 'active' ? 'success' : 'secondary' }}">{{ $r->status ?? 'active' }}</span></td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center">No hubs found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if(method_exists($rows, 'links'))<div class="mt-3">{{ $rows->links() }}</div>@endif
          @endif
        </div>
      </div>
    </div></div></div></div></div>
  </div>
</div>
@endsection
