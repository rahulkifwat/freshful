@extends('country_manager.layout.master')

@section('content')
<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row"><div class="full-wdt"><div class="contents-inner">

      <div class="row">
        @php
          $cards = [
            ['label'=>'Customers',    'value'=>$total_buyers,   'color'=>'primary',   'icon'=>'fa-users'],
            ['label'=>'Total Orders', 'value'=>$total_orders,   'color'=>'success',   'icon'=>'fa-shopping-cart'],
            ['label'=>"Today's Orders",'value'=>$today_orders,  'color'=>'info',      'icon'=>'fa-calendar'],
            ['label'=>'Products',     'value'=>$total_products, 'color'=>'warning',   'icon'=>'fa-product-hunt'],
            ['label'=>'Hubs',         'value'=>$total_hubs,     'color'=>'secondary', 'icon'=>'fa-building'],
            ['label'=>'Grievances',   'value'=>$grievance_count,'color'=>'dark',      'icon'=>'fa-exclamation-circle'],
            ['label'=>'Danger Stock', 'value'=>$danger_count,   'color'=>'danger',    'icon'=>'fa-warning'],
            ['label'=>'Wastage',      'value'=>$wastage_count,  'color'=>'secondary', 'icon'=>'fa-trash'],
          ];
        @endphp
        @foreach($cards as $card)
        <div class="col-xl-3 col-md-4 mb-4">
          <div class="card border-left-{{ $card['color'] }} shadow h-100 py-2">
            <div class="card-body">
              <div class="row no-gutters align-items-center">
                <div class="col mr-2">
                  <div class="text-xs font-weight-bold text-{{ $card['color'] }} text-uppercase mb-1">{{ $card['label'] }}</div>
                  <div class="h5 mb-0 font-weight-bold">{{ number_format($card['value']) }}</div>
                </div>
                <div class="col-auto"><i class="fa {{ $card['icon'] }} fa-2x text-gray-300"></i></div>
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>

      <div class="row mt-2">
        <div class="col-12">
          <div class="card shadow">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Quick Links</h6></div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-2 mb-2"><a href="{{ route('country_manager.all_orders') }}" class="btn btn-outline-success btn-block btn-sm">All Orders</a></div>
                <div class="col-md-2 mb-2"><a href="{{ route('country_manager.all_customers') }}" class="btn btn-outline-primary btn-block btn-sm">Customers</a></div>
                <div class="col-md-2 mb-2"><a href="{{ route('country_manager.hubs') }}" class="btn btn-outline-secondary btn-block btn-sm">Hubs</a></div>
                <div class="col-md-2 mb-2"><a href="{{ route('country_manager.danger_stock') }}" class="btn btn-outline-danger btn-block btn-sm">Danger Stock</a></div>
                <div class="col-md-2 mb-2"><a href="{{ route('country_manager.sales_report') }}" class="btn btn-outline-info btn-block btn-sm">Sales Report</a></div>
                <div class="col-md-2 mb-2"><a href="{{ route('country_manager.day_end_report') }}" class="btn btn-outline-warning btn-block btn-sm">Day-End Report</a></div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div></div></div>
  </div>
</div>
@endsection
