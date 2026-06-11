@extends('customer_care_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">
        <div class="content-head"><h4 class="content-title">Wastage Reports</h4></div>
        <div class="content-details show">
          @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty())
            <div class="alert alert-info">No wastage records found.</div>
          @else
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr><th>ID</th><th>Product</th><th>Hub</th>
                  @if($rows->count())
                    @foreach(array_keys((array) $rows->first()) as $col)
                      @if(!in_array($col, ['id','product_name','hub_name','product_id','hub_id']))
                        <th>{{ ucwords(str_replace('_',' ',$col)) }}</th>
                      @endif
                    @endforeach
                  @endif
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $r)
                  @php $arr = (array) $r; @endphp
                  <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->product_name ?? '—' }}</td>
                    <td>{{ $r->hub_name ?? '—' }}</td>
                    @foreach($arr as $key => $val)
                      @if(!in_array($key, ['id','product_name','hub_name','product_id','hub_id']))
                        <td>{{ $val ?? '—' }}</td>
                      @endif
                    @endforeach
                  </tr>
                @empty
                  <tr><td colspan="10" class="text-center">No wastage records found.</td></tr>
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
