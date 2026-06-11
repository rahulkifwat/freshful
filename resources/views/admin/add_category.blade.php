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
                  <h4 class="content-title">Main Categories</h4>
                  <a href="{{ route('admin.main_category_form') }}" class="btn btn-primary btn-sm" style="float:right;">
                    <i class="fa fa-dot-circle-o"></i> Add Category
                  </a>
                </div>

                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

                <div class="content-details show">
                  <form method="get" action="{{ route('admin.main_categories') }}" class="mb-3">
                    <div class="row">
                      <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search category" value="{{ request('search') }}">
                      </div>
                      <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if(request('search'))<a href="{{ route('admin.main_categories') }}" class="btn btn-outline-secondary">Reset</a>@endif
                      </div>
                    </div>
                  </form>

                  <div class="table-responsive">
                    <table class="table data-table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Image</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($rows as $r)
                          <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->name }}</td>
                            <td>
                              @if(!empty($r->image))
                                <img src="{{ asset('uploads/images/categories/'.$r->image) }}" height="40" alt="">
                              @else <span class="text-muted">—</span>
                              @endif
                            </td>
                            <td>
                              <label class="switch">
                                <input type="checkbox" class="status_enable switch-warning change_status"
                                       data-table="categories" data-id="{{ $r->id }}"
                                       value="{{ $r->status ?? 'active' }}"
                                       {{ ($r->status ?? 'active') === 'active' ? 'checked' : '' }}>
                                <span class="slider round"></span>
                              </label>
                            </td>
                            <td>
                              <a href="{{ route('admin.main_category_form', ['id' => $r->id]) }}" class="btn btn-info btn-sm">
                                <i class="fa fa-pencil-square-o"></i>
                              </a>
                              <button type="button" data-table="categories" data-id="{{ $r->id }}"
                                      class="btn btn-danger btn-sm deleteRecord">
                                <i class="fa fa-trash"></i>
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="5" class="text-center">No categories found.</td></tr>
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
