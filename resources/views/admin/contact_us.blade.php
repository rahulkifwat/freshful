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
                  <h4 class="content-title">Contact Us Queries</h4>
                </div>

                <div class="content-details show">
                  <div class="table-responsive">
                    <table id="data-table" class="table data-table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Email</th>
                          <th>Phone</th>
                          <th>Query</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($rows as $r)
                          <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ trim(($r->fname ?? '').' '.($r->lname ?? '')) }}</td>
                            <td>{{ $r->email }}</td>
                            <td>{{ $r->phone ?? '-' }}</td>
                            <td>{{ $r->query ?? '' }}</td>
                          </tr>
                        @empty
                          <tr><td colspan="5" class="text-center">No queries yet.</td></tr>
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
