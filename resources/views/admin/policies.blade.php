@extends('admin.layout.master')

@section('content')
@php
  $knownTypes = ['terms', 'refund_policy', 'shipping_policy', 'about_us', 'why-freshful'];
  $existingTypes = $all->pluck('type')->all();
  $types = collect(array_unique(array_merge($knownTypes, $existingTypes)))->filter()->values();
@endphp

<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">
      <div class="full-wdt">
        <div class="col-md-12">
          <div class="section-content">

            <div class="content-head">
              <h4 class="content-title">Policies</h4>
            </div>

            <div class="content-details show">
              @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
              @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
              @endif

              <div class="row">
                <div class="col-md-3">
                  <div class="list-group">
                    @foreach($types as $t)
                      <a href="{{ route('admin.policies', ['type' => $t]) }}"
                         class="list-group-item {{ $type === $t ? 'active' : '' }}">
                        {{ $t }}
                      </a>
                    @endforeach
                    <a href="{{ route('admin.policies', ['type' => '__new__']) }}"
                       class="list-group-item {{ $type === '__new__' ? 'active' : '' }}">+ New Policy</a>
                  </div>
                </div>

                <div class="col-md-9">
                  @if($type)
                    <div class="card pay-invoice">
                      <div class="card-body">
                        <form method="post" action="{{ route('admin.policies_update') }}">
                          @csrf
                          <div class="form-group">
                            <label>Type</label>
                            <input type="text" name="type" class="form-control"
                                   value="{{ old('type', $type === '__new__' ? '' : $type) }}"
                                   {{ $type !== '__new__' ? 'readonly' : '' }}
                                   placeholder="e.g. terms">
                          </div>
                          <div class="form-group">
                            <label>Content</label>
                            <textarea name="content" class="form-control" rows="18" required>{{ old('content', $current->content ?? '') }}</textarea>
                          </div>
                          <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa fa-dot-circle-o"></i> Save
                          </button>
                        </form>
                      </div>
                    </div>
                  @else
                    <p class="text-muted">Pick a policy on the left to edit, or click "New Policy" to create one.</p>
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
