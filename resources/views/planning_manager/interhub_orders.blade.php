@extends('planning_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">Inter-Hub Orders</h4></div>
        <div class="content-details show">
          @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty())
            <div class="alert alert-info">No inter-hub order data available.</div>
          @else
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr><th>Order ID</th><th>Buyer</th><th>Phone</th><th>Hub</th><th>Status</th><th>Date</th><th>View</th></tr>
              </thead>
              <tbody>
                @forelse($rows as $o)
                  <tr>
                    <td>{{ $o->order_id }}</td>
                    <td>{{ $o->buyer_name ?? '—' }}</td>
                    <td>{{ $o->buyer_phone ?? '—' }}</td>
                    <td>{{ $o->hub_name ?? '—' }}</td>
                    <td><span class="badge badge-info">{{ $o->order_status ?? '—' }}</span></td>
                    <td>{{ $o->created_at ?? '—' }}</td>
                    <td>
                      <a href="{{ route('planning_manager.view_order', ['order_id' => $o->order_id]) }}"
                         class="btn btn-info btn-sm"><i class="fa fa-eye"></i></a>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="text-center">No records found.</td></tr>
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
