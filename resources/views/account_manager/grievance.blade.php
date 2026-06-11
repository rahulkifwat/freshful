@extends('account_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner"><div class="row"><div class="col-12">
      <div class="section-content">

        <div class="content-head">
          <h4 class="content-title">Grievance</h4>
        </div>

        <div class="content-details show">
          @if($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty())
            <div class="alert alert-info">No grievance table found or no records yet.</div>
          @else
          <div class="table-responsive">
            <table class="table data-table table-striped table-bordered">
              <thead>
                <tr>
                  <th>ID</th>
                  @if($rows->count())
                    @foreach(array_keys((array) $rows->first()) as $col)
                      @if($col !== 'id')<th>{{ ucwords(str_replace('_', ' ', $col)) }}</th>@endif
                    @endforeach
                  @endif
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $r)
                  @php $arr = (array) $r; @endphp
                  <tr>
                    <td>{{ $r->id }}</td>
                    @foreach($arr as $key => $val)
                      @if($key !== 'id')<td>{{ $val ?? '—' }}</td>@endif
                    @endforeach
                    <td>
                      <button type="button" data-table="grievance" data-id="{{ $r->id }}"
                              class="btn btn-danger btn-sm deleteRecord">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="10" class="text-center">No grievances found.</td></tr>
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
