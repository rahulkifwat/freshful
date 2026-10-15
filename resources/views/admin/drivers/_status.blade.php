{{-- Approval + account badges for a driver. Expects $verification (string|null) and $suspended (bool). --}}
@php
  $approval = [
    'pending_admin_approval' => ['Pending approval', 'warning'],
    'approved'               => ['Approved', 'success'],
    'rejected'               => ['Rejected', 'danger'],
  ][$verification ?? ''] ?? ['Incomplete', 'secondary'];
@endphp
<span class="badge badge-{{ $approval[1] }}">{{ $approval[0] }}</span>
@if($suspended)
  <span class="badge badge-dark">Suspended</span>
@endif
