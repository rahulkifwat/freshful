@extends('admin.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="contents-inner">
          <div class="row">
            <div class="col-md-5">
              <div class="section-content">
                <div class="content-head">
                  <h4 class="content-title">Send Notification</h4>
                </div>

                <div class="content-details show">
                  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                  @if($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                  @endif

                  <div class="card pay-invoice">
                    <div class="card-body">
                      <form method="post" action="{{ route('admin.push_send') }}">
                        @csrf
                        <div class="form-group">
                          <label>Title</label>
                          <input type="text" name="title" class="form-control" required value="{{ old('title') }}">
                        </div>
                        <div class="form-group">
                          <label>Description</label>
                          <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                          <i class="fa fa-paper-plane"></i> Queue
                        </button>
                      </form>
                      <p class="text-muted small mt-3">
                        Notifications are saved to <code>push_noti</code>. Sending to devices (FCM/APNs) is done by an external worker.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-7">
              <div class="section-content">
                <div class="content-head">
                  <h4 class="content-title">History</h4>
                </div>

                <div class="content-details show">
                  <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Title</th>
                          <th>Description</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($rows as $r)
                          <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->title }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($r->description ?? '', 80) }}</td>
                            <td>
                              <button type="button" class="btn btn-danger btn-sm deleteRecord"
                                      data-table="push_noti" data-id="{{ $r->id }}">
                                <i class="fa fa-trash"></i>
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="4" class="text-center">No notifications yet.</td></tr>
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
