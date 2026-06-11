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
                  <h4 class="content-title">Order #{{ $order->order_id }}</h4>
                  <a href="{{ route('admin.all_orders') }}" class="btn btn-secondary btn-sm" style="float:right;">
                    <i class="fa fa-arrow-left"></i> Back
                  </a>
                </div>

                <div class="content-details show">

                  <div class="row">
                    <div class="col-md-6">
                      <div class="card mb-3">
                        <div class="card-body">
                          <h5>Customer</h5>
                          <p><strong>Name:</strong> {{ $buyer->name ?? '-' }}</p>
                          <p><strong>Email:</strong> {{ $buyer->email ?? '-' }}</p>
                          @if($address)
                            <hr>
                            <h6>Delivery Address</h6>
                            <p>{{ trim(($address->address ?? '').' '.($address->address2 ?? '')) }}</p>
                            <p>{{ $address->city ?? '' }} {{ $address->pincode ?? '' }}</p>
                            <p>Phone: {{ $address->phone ?? '-' }}</p>
                          @endif
                        </div>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="card mb-3">
                        <div class="card-body">
                          <h5>Order</h5>
                          <p><strong>Status:</strong>
                            <span class="badge
                              @if($order->order_status == 'Order Pending') badge-secondary
                              @elseif($order->order_status == 'Order Cancel') badge-danger
                              @elseif($order->order_status == 'Order Placed') badge-info
                              @elseif($order->order_status == 'Order Processed') badge-warning
                              @elseif($order->order_status == 'Order Shipped') badge-primary
                              @else badge-success
                              @endif">
                              {{ $order->order_status }}
                            </span>
                          </p>
                          <p><strong>Placed:</strong> {{ $order->date_added ? \Carbon\Carbon::parse($order->date_added)->format('d-m-Y H:i') : '-' }}</p>
                          <p><strong>Delivery Type:</strong> {{ $order->delivery_type ?? '-' }}</p>
                          @if(!empty($order->schedule_date))
                            <p><strong>Scheduled:</strong> {{ $order->schedule_date }} {{ $order->schedule_time ?? '' }}</p>
                          @endif
                          <p><strong>Hub:</strong> {{ $hub->hub ?? '-' }}</p>
                          <p><strong>Payment:</strong> {{ $order->payment_type ?? '-' }} ({{ $order->payment_status ?? '-' }})</p>
                          @if(!empty($order->delivery_charge))
                            <p><strong>Delivery Charge:</strong> {{ $order->delivery_charge }}</p>
                          @endif
                          <p><strong>Total:</strong> {{ $order->total_amount }}</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="content-head mt-3">
                    <h4 class="content-title">Items</h4>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Item</th>
                          <th>Unit</th>
                          <th>Qty</th>
                          <th>Price</th>
                          <th>MRP</th>
                          <th>Subtotal</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($items as $i => $it)
                          <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $it->item ?? '-' }}</td>
                            <td>{{ trim(($it->unit_quantity ?? '').' '.($it->product_unit ?? '')) }}</td>
                            <td>{{ $it->quantity ?? '-' }}</td>
                            <td>{{ $it->product_main_price ?? $it->price ?? '-' }}</td>
                            <td>{{ $it->product_mrp ?? '-' }}</td>
                            <td>{{ isset($it->price, $it->quantity) ? number_format($it->price * $it->quantity, 2) : '-' }}</td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
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
@endsection
