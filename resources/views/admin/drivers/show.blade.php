@extends('admin.layout.master')

@section('content')
@include('admin.drivers._layout_fix')
@php
  $name = ($profile->name ?? null) ?: ($driver->name ?: $driver->email);
  $fmt = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('d M Y, h:i a') : '—';
  $docPairs = [
    'Aadhaar card'    => ['aadhaar_card_front', 'aadhaar_card_back', 'aadhaar_card_status'],
    'Driving licence' => ['driving_license_front', 'driving_license_back', 'driving_license_status'],
    'PAN card'        => ['pan_card_front', 'pan_card_back', 'pan_card_status'],
  ];
@endphp
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="contents-inner">
          <div class="row">
            <div class="col-12">
              <div class="section-content">
                <div class="content-head d-flex flex-wrap align-items-center justify-content-between">
                  <h4 class="content-title">Driver: {{ $name }}</h4>
                  <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.drivers.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
                </div>

                <div class="content-details show">

                  {{-- Summary + approval actions --}}
                  <div class="card mb-3">
                    <div class="card-body d-flex flex-wrap align-items-center">
                      <div class="mr-3 mb-2">
                        @if($photo_url)
                          <img src="{{ $photo_url }}" alt="" width="90" height="90" style="object-fit:cover;border-radius:50%"
                               onerror="this.replaceWith(document.createTextNode('No photo'))">
                        @else
                          <div class="text-muted">No photo</div>
                        @endif
                      </div>
                      <div class="mr-auto mb-2">
                        <h5 class="mb-1">{{ $name }}</h5>
                        <div>{{ $driver->phone ?: '—' }} · {{ $driver->email ?: '—' }}</div>
                        <div class="mt-1">@include('admin.drivers._status', ['verification' => $driver->verification_status, 'suspended' => $suspended, 'working' => ($profile->working_status ?? null) ?: 'off_duty'])</div>
                      </div>
                      @if($driver->verification_status === 'pending_admin_approval')
                        <div class="mb-2">
                          <button type="button" class="btn btn-success js-driver-action js-driver-approve" data-id="{{ $driver->id }}" data-name="{{ $name }}">Approve</button>
                          <button type="button" class="btn btn-outline-danger js-driver-action js-driver-reject" data-id="{{ $driver->id }}" data-name="{{ $name }}">Reject</button>
                        </div>
                      @endif
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-6 mb-3">
                      <div class="card h-100"><div class="card-body">
                        <h6 class="mb-3">Verification</h6>
                        <table class="table table-sm mb-0">
                          <tr><th>Driver ID</th><td>{{ $driver->id }}</td></tr>
                          <tr><th>Employee no.</th><td>{{ $driver->emp_no ?: '—' }}{{ ($hub->employee_id ?? null) ? ' ('.$hub->employee_id.')' : '' }}</td></tr>
                          <tr><th>Account status</th><td>
                            <label class="switch mb-0" title="{{ $suspended ? 'Deactive: click to activate' : 'Active: click to deactivate' }}">
                                <input type="checkbox" class="status_enable js-driver-status" data-id="{{ $driver->id }}" data-name="{{ $name }}" {{ $suspended ? '' : 'checked' }}>
                                <span class="slider round"></span>
                              </label>
                            <span class="ml-2">{{ $suspended ? 'Deactive' : 'Active' }}</span>
                          </td></tr>
                          <tr><th>Submitted for approval</th><td>{{ $fmt($driver->verified_at) }}</td></tr>
                          <tr><th>{{ $driver->verification_status === 'rejected' ? 'Rejected' : 'Approved' }} at</th><td>{{ $fmt($driver->approved_at) }}{{ $driver->approved_by ? ' (admin #'.$driver->approved_by.')' : '' }}</td></tr>
                          @if($driver->verification_status === 'rejected')
                            <tr><th>Rejection reason</th><td>{{ $driver->rejection_reason ?: '—' }}</td></tr>
                          @endif
                          <tr><th>Working status</th><td>{{ (($profile->working_status ?? 'off_duty') === 'on_duty') ? 'On duty' : 'Off duty' }}{{ ($profile->working_status_at ?? null) ? ' (since '.$fmt($profile->working_status_at).')' : '' }}</td></tr>
                          <tr><th>Current location</th><td>
                            @if($location)
                              {{ $location->address ?: 'No address' }}<br>
                              <a href="https://www.google.com/maps?q={{ $location->lat }},{{ $location->lng }}" target="_blank" rel="noopener">{{ $location->lat }}, {{ $location->lng }}</a>
                              <br><small class="text-muted">Updated {{ $fmt($location->updated_at) }}</small>
                            @else
                              —
                            @endif
                          </td></tr>
                          <tr><th>Hub</th><td>{{ $hub->hub ?? '—' }}</td></tr>
                          <tr><th>Open assigned orders</th><td>{{ $active_orders }}</td></tr>
                          <tr><th>Wallet</th><td>{{ $wallet ? $wallet->currency.' '.$wallet->balance : 'Not created (created on approval)' }}</td></tr>
                        </table>
                      </div></div>
                    </div>

                    <div class="col-lg-6 mb-3">
                      <div class="card h-100"><div class="card-body">
                        <h6 class="mb-3">Personal information</h6>
                        @if($profile)
                          <table class="table table-sm mb-0">
                            <tr><th>Father's name</th><td>{{ $profile->father_name ?: '—' }}</td></tr>
                            <tr><th>Date of birth</th><td>{{ $profile->dob ?: '—' }}</td></tr>
                            <tr><th>Profile phone</th><td>{{ $profile->phone ?: '—' }}</td></tr>
                            <tr><th>Blood group</th><td>{{ $profile->blood_group ?: '—' }}</td></tr>
                            <tr><th>Address</th><td>{{ $profile->address ?: '—' }}</td></tr>
                            <tr><th>Language</th><td>{{ $profile->language ?: '—' }}</td></tr>
                            <tr><th>Emergency contact</th><td>{{ trim(($profile->emergency_name ?? '').' '.($profile->emergency_relationship ? '('.$profile->emergency_relationship.')' : '').' '.($profile->emergency_phone ?? '')) ?: '—' }}</td></tr>
                            <tr><th>Referral code</th><td>{{ $profile->referral_code ?: '—' }}</td></tr>
                            <tr><th>Profile created</th><td>{{ $fmt($profile->created_at ?? null) }}</td></tr>
                          </table>
                        @else
                          <p class="text-muted mb-0">The driver hasn't filled in personal information yet.</p>
                        @endif
                      </div></div>
                    </div>

                    <div class="col-lg-6 mb-3">
                      <div class="card h-100"><div class="card-body">
                        <h6 class="mb-3">Vehicle</h6>
                        @if($vehicle)
                          <table class="table table-sm mb-0">
                            <tr><th>Type</th><td>{{ $vehicle->vehicle_type ?: '—' }}</td></tr>
                            <tr><th>Number</th><td>{{ $vehicle->vehicle_number ?: '—' }}</td></tr>
                          </table>
                        @else
                          <p class="text-muted mb-0">No vehicle details yet.</p>
                        @endif
                      </div></div>
                    </div>

                    <div class="col-lg-6 mb-3">
                      <div class="card h-100"><div class="card-body">
                        <h6 class="mb-3">Bank details</h6>
                        @if($bank)
                          <table class="table table-sm mb-0">
                            <tr><th>Account holder</th><td>{{ $bank->account_holder_name ?: '—' }}</td></tr>
                            <tr><th>Account number</th><td>{{ $bank->account_number ?: '—' }}</td></tr>
                            <tr><th>IFSC</th><td>{{ $bank->ifsc_code ?: '—' }}</td></tr>
                            <tr><th>Bank / branch</th><td>{{ trim(($bank->bank_name ?? '').' / '.($bank->branch_name ?? ''), ' /') ?: '—' }}</td></tr>
                          </table>
                        @else
                          <p class="text-muted mb-0">No bank details yet.</p>
                        @endif
                      </div></div>
                    </div>
                  </div>

                  <div class="card mb-3"><div class="card-body">
                    <h6 class="mb-3">Identity documents</h6>
                    @if($documents)
                      <div class="row">
                        @foreach($docPairs as $label => [$front, $back, $statusField])
                          <div class="col-md-4 mb-3">
                            <div class="font-weight-bold mb-1">{{ $label }}
                              @if(! empty($documents->$statusField))
                                <span class="badge badge-info">{{ $documents->$statusField }}</span>
                              @endif
                            </div>
                            <div class="d-flex">
                              @foreach([$front => 'Front', $back => 'Back'] as $field => $side)
                                <div class="mr-2 text-center">
                                  @if($document_urls[$field])
                                    <a href="{{ $document_urls[$field] }}" target="_blank" rel="noopener">
                                      <img src="{{ $document_urls[$field] }}" alt="{{ $label }} {{ $side }}" width="120" height="80"
                                           style="object-fit:cover;border:1px solid #ddd" onerror="this.replaceWith(document.createTextNode('Open file'))">
                                    </a>
                                  @else
                                    <div class="text-muted border d-flex align-items-center justify-content-center" style="width:120px;height:80px">Missing</div>
                                  @endif
                                  <small>{{ $side }}</small>
                                </div>
                              @endforeach
                            </div>
                          </div>
                        @endforeach
                      </div>
                    @else
                      <p class="text-muted mb-0">No documents uploaded yet.</p>
                    @endif
                  </div></div>

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
