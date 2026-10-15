{{-- Approval, account and (optional) duty badges. Expects $verification, $suspended (bool), optional $working. --}}
@php
  $approval = [
    'pending_admin_approval' => ['Pending approval', 'warning'],
    'approved'               => ['Approved', 'success'],
    'rejected'               => ['Rejected', 'danger'],
  ][$verification ?? ''] ?? ['Incomplete', 'secondary'];
@endphp
<span class="badge badge-{{ $approval[1] }}">{{ $approval[0] }}</span>
@if($suspended)
  <span class="badge badge-dark">Deactive</span>
@endif
@isset($working)
  @if($working === 'on_duty')
    <span class="badge badge-primary">On duty</span>
  @else
    <span class="badge badge-light border">Off duty</span>
  @endif
@endisset
