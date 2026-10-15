@extends('admin.layout.master')

@section('content')
@php
  $tabs = [null => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'suspended' => 'Suspended', 'incomplete' => 'Incomplete'];
  $isPendingPage = request()->routeIs('admin.drivers.pending');
@endphp
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="contents-inner">
          <div class="row">
            <div class="col-12">
              <div class="section-content">
                <div class="content-head">
                  <h4 class="content-title">{{ $title }}</h4>
                </div>

                <div class="content-details show">

                  @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                  @endif

                  @unless($isPendingPage)
                    <ul class="nav nav-pills mb-3 flex-wrap">
                      @foreach($tabs as $key => $label)
                        <li class="nav-item">
                          <a class="nav-link {{ $filter === ($key ?: null) ? 'active' : '' }}"
                             href="{{ route('admin.drivers.index', array_filter(['status' => $key ?: null, 'search' => request('search')])) }}">{{ $label }}</a>
                        </li>
                      @endforeach
                    </ul>
                  @endunless

                  <form method="get" action="{{ $isPendingPage ? route('admin.drivers.pending') : route('admin.drivers.index') }}" class="mb-3">
                    @if($filter && ! $isPendingPage)
                      <input type="hidden" name="status" value="{{ $filter }}">
                    @endif
                    <div class="row">
                      <div class="col-md-5 col-sm-8 mb-2">
                        <input type="text" name="search" class="form-control"
                               placeholder="Search by name, email, phone or vehicle number"
                               value="{{ request('search') }}">
                      </div>
                      <div class="col-md-3 col-sm-4 mb-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if(request('search'))
                          <a href="{{ $isPendingPage ? route('admin.drivers.pending') : route('admin.drivers.index', array_filter(['status' => $filter])) }}" class="btn btn-outline-secondary">Reset</a>
                        @endif
                      </div>
                    </div>
                  </form>

                  <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Photo</th>
                          <th>Name</th>
                          <th>Phone</th>
                          <th>Email</th>
                          <th>Vehicle</th>
                          <th>Profile created</th>
                          <th>Status</th>
                          <th style="min-width: 190px">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($drivers as $d)
                          <tr>
                            <td>{{ $d->id }}</td>
                            <td>
                              @if($d->photo_url)
                                <img src="{{ $d->photo_url }}" alt="" width="40" height="40" style="object-fit:cover;border-radius:50%"
                                     onerror="this.replaceWith(document.createTextNode('—'))">
                              @else
                                —
                              @endif
                            </td>
                            <td>{{ $d->name ?: '—' }}</td>
                            <td>{{ $d->phone ?: '—' }}</td>
                            <td>{{ $d->email ?: '—' }}</td>
                            <td>
                              @if($d->vehicle_type || $d->vehicle_number)
                                {{ $d->vehicle_type }}<br><small class="text-muted">{{ $d->vehicle_number }}</small>
                              @else
                                —
                              @endif
                            </td>
                            <td>{{ $d->profile_created_at ? \Illuminate\Support\Carbon::parse($d->profile_created_at)->format('d M Y') : '—' }}</td>
                            <td>@include('admin.drivers._status', ['verification' => $d->verification_status, 'suspended' => $d->suspended])</td>
                            <td>
                              <a href="{{ route('admin.drivers.show', ['id' => $d->id]) }}" class="btn btn-outline-primary btn-sm mb-1">View</a>
                              @if($d->verification_status === 'pending_admin_approval')
                                <button type="button" class="btn btn-success btn-sm mb-1 js-driver-action js-driver-approve"
                                        data-id="{{ $d->id }}" data-name="{{ $d->name ?: $d->email }}">Approve</button>
                                <button type="button" class="btn btn-outline-danger btn-sm mb-1 js-driver-action js-driver-reject"
                                        data-id="{{ $d->id }}" data-name="{{ $d->name ?: $d->email }}">Reject</button>
                              @endif
                            </td>
                          </tr>
                        @empty
                          <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                              {{ request('search') ? 'No drivers match your search.' : ($isPendingPage ? 'No drivers are waiting for approval.' : 'No drivers found.') }}
                            </td>
                          </tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>

                  <div class="mt-3">
                    {{ $drivers->links() }}
                  </div>

                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@include('admin.drivers._approval_js')
@endsection
