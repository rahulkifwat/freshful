@extends('account_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">

        <div class="content-head">
          <h4 class="content-title">Delivery Boy</h4>
        </div>

        <div class="content-details show">
          @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty())
            <div class="alert alert-info">No delivery boy table found or no records yet.</div>
          @else
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Phone</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $r)
                  <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->name ?? '—' }}</td>
                    <td>{{ $r->phone ?? ($r->mobile ?? '—') }}</td>
                    <td>{{ $r->email ?? '—' }}</td>
                    <td>
                      <label class="switch">
                        <input type="checkbox" class="status_enable switch-warning change_status"
                               data-table="delivery_boy" data-id="{{ $r->id }}"
                               value="{{ $r->status ?? 'active' }}"
                               {{ ($r->status ?? 'active') === 'active' ? 'checked' : '' }}>
                        <span class="slider round"></span>
                      </label>
                    </td>
                    <td>
                      <button type="button" data-table="delivery_boy" data-id="{{ $r->id }}"
                              class="btn btn-danger btn-sm deleteRecord">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center">No delivery boys found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if(method_exists($rows, 'links'))
            <div class="mt-3">{{ $rows->links() }}</div>
          @endif
          @endif
        </div>
      </div>
    </div></div></div></div></div>
  </div>
</div>
@endsection
