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
                  <h4 class="content-title">Role Assignments</h4>
                </div>

                <div class="content-details show">
                  <div class="table-responsive">
                    <table id="data-table" class="table data-table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>User</th>
                          <th>Role</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($rows as $r)
                          <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->user_id ?? $r->user_email ?? '-' }}</td>
                            <td>{{ $r->role_name ?? '-' }}</td>
                            <td>
                              <button type="button" class="btn btn-danger btn-sm deleteRecord"
                                      data-table="role_user" data-id="{{ $r->id }}">
                                <i class="fa fa-trash"></i>
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="4" class="text-center">No assignments.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>
                  @if(method_exists($rows, 'links'))
                    <div class="mt-3">{{ $rows->links() }}</div>
                  @endif
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
