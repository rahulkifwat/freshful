@extends('admin_new.layout')

@section('content')
  <div class="ff-ph">
    <div>
      <h1>Dashboard</h1>
      <p>Freshful admin console</p>
    </div>
  </div>

  <section class="ff-panel ff-empty">
    <div class="ff-empty-ic">{!! \App\Support\AdminNew\Icons::svg('grid', 22) !!}</div>
    <h2>The live dashboard is built in Phase 2</h2>
    <p>The new menu is ready to use. Pages with an arrow open the classic admin screen until each one is rebuilt here. Pages marked Soon arrive in later phases.</p>
    <div class="ff-empty-acts">
      <a class="ff-btn ff-pri" href="{{ url('admin/all_orders') }}">Open all orders</a>
      <a class="ff-btn" href="{{ url('admin/dashboard') }}">Classic dashboard</a>
    </div>
  </section>
@endsection
