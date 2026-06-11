@extends('hr_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">News Letters</h4></div>
        <div class="content-details show">
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr><th>ID</th><th>Email</th><th>Date</th></tr>
              </thead>
              <tbody>
                @forelse($rows as $r)
                  <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->email ?? '—' }}</td>
                    <td>{{ $r->created_at ?? '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="text-center">No subscribers found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          <div class="mt-3">{{ $rows->links() }}</div>
        </div>
      </div>
    </div></div></div></div></div>
  </div>
</div>
@endsection
