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
                  <h4 class="content-title">Promotions</h4>
                  <a href="{{ route('admin.promotion_form') }}" class="btn btn-primary btn-sm" style="float:right;">
                    <i class="fa fa-dot-circle-o"></i> Add Promotion
                  </a>
                </div>

                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

                <div class="content-details show">

                  <div class="table-responsive">
                    <table id="data-table" class="table data-table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Promocode</th>
                          <th>Image</th>
                          <th>Description</th>
                          <th>%</th>
                          <th>Min Amt</th>
                          <th>Expiry</th>
                          <th>Eligibility</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($rows as $r)
                          <tr>
                            <td>{{ $r->id }}</td>
                            <td>{{ $r->title ?? '-' }}</td>
                            <td>
                              @if(!empty($r->image))
                                <img src="{{ asset('uploads/images/promotions/'.$r->image) }}" height="50" width="120" alt="">
                              @else
                                -
                              @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($r->description ?? '', 50) }}</td>
                            <td>{{ $r->percentage ?? '-' }}</td>
                            <td>{{ $r->min_amount ?? '-' }}</td>
                            <td>{{ $r->expiry ?? '-' }}</td>
                            <td>{{ $r->user_eligible ?? '-' }}</td>
                            <td>
                              <label class="switch">
                                <input type="checkbox" name="status"
                                       class="status_enable switch-warning change_status"
                                       data-table="home_promotions"
                                       data-id="{{ $r->id }}"
                                       value="{{ $r->status ?? 'active' }}"
                                       {{ ($r->status ?? 'active') === 'active' ? 'checked' : '' }}>
                                <span class="slider round"></span>
                              </label>
                            </td>
                            <td>
                              <a href="{{ route('admin.promotion_form', ['id' => $r->id]) }}" class="btn btn-info btn-sm">
                                <i class="fa fa-pencil-square-o"></i>
                              </a>
                              <button type="button" class="btn btn-danger btn-sm deleteRecord"
                                      data-table="home_promotions" data-id="{{ $r->id }}">
                                <i class="fa fa-trash"></i>
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="10" class="text-center">No promotions yet.</td></tr>
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
