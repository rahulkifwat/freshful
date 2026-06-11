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
                  <h4 class="content-title">Buyer Detail</h4>
                  <a href="{{ route('admin.buyers') }}" class="btn btn-secondary btn-sm" style="float:right;">
                    <i class="fa fa-arrow-left"></i> Back
                  </a>
                </div>

                <div class="content-details show">
                  <div class="card">
                    <div class="card-body">
                      <div class="row">
                        <div class="col-md-6">
                          <p><strong>ID:</strong> {{ $buyer->id }}</p>
                          <p><strong>Name:</strong> {{ $buyer->name }}</p>
                          <p><strong>Email:</strong> {{ $buyer->email }}</p>
                          <p><strong>Phone:</strong> {{ $buyer->phone ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                          <p><strong>Wallet:</strong> {{ $buyer->wallet_amount ?? 0 }}</p>
                          <p><strong>Status:</strong>
                            <span class="badge badge-{{ ($buyer->status ?? 'inactive') === 'active' ? 'success' : 'secondary' }}">
                              {{ $buyer->status ?? '-' }}
                            </span>
                          </p>
                          @if(!empty($buyer->date_added))
                            <p><strong>Joined:</strong> {{ \Carbon\Carbon::parse($buyer->date_added)->format('d-m-Y') }}</p>
                          @endif
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="content-head mt-4">
                    <h4 class="content-title">Addresses</h4>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Address</th>
                          <th>City</th>
                          <th>Phone</th>
                          <th>Pincode</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($addresses as $i => $a)
                          <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ trim(($a->address ?? '').' '.($a->address2 ?? '')) }}</td>
                            <td>{{ $a->city ?? '-' }}</td>
                            <td>{{ $a->phone ?? '-' }}</td>
                            <td>{{ $a->pincode ?? '-' }}</td>
                          </tr>
                        @empty
                          <tr><td colspan="5" class="text-center">No addresses on file.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>

                  <div class="content-head mt-4">
                    <h4 class="content-title">Recent Orders</h4>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>Order ID</th>
                          <th>Date</th>
                          <th>Type</th>
                          <th>Amount</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($orders as $o)
                          <tr>
                            <td>{{ $o->order_id }}</td>
                            <td>{{ $o->date_added ? \Carbon\Carbon::parse($o->date_added)->format('d-m-Y') : '-' }}</td>
                            <td>{{ $o->delivery_type ?? '-' }}</td>
                            <td>{{ $o->total_amount }}</td>
                            <td>
                              <span class="badge
                                @if($o->order_status == 'Order Pending') badge-secondary
                                @elseif($o->order_status == 'Order Cancel') badge-danger
                                @elseif($o->order_status == 'Order Placed') badge-info
                                @elseif($o->order_status == 'Order Processed') badge-warning
                                @elseif($o->order_status == 'Order Shipped') badge-primary
                                @else badge-success
                                @endif">
                                {{ $o->order_status }}
                              </span>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="5" class="text-center">No orders yet.</td></tr>
                        @endforelse
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
