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
                  <h4 class="content-title">Sale Summary</h4>
                </div>

                <div class="content-details show">
                  <form method="get" action="{{ route('admin.sale_summary') }}" class="mb-3">
                    <div class="row">
                      <div class="col-md-3">
                        <label>From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                      </div>
                      <div class="col-md-3">
                        <label>To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                      </div>
                      <div class="col-md-3 align-self-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        @if(request()->hasAny(['date_from','date_to']))
                          <a href="{{ route('admin.sale_summary') }}" class="btn btn-outline-secondary">Reset</a>
                        @endif
                      </div>
                    </div>
                  </form>

                  <div class="row mb-3">
                    <div class="col-md-3">
                      <div class="statistic-box m-0">
                        <h4 class="statistic-title float-left">Delivered Orders</h4>
                        <div class="statistic-details">
                          <span class="count float-left">{{ $totals->total_orders ?? 0 }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="statistic-box m-0">
                        <h4 class="statistic-title float-left">Total Revenue</h4>
                        <div class="statistic-details">
                          <span class="count float-left">{{ number_format((float) $totalRevenue, 2) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table id="data-table" class="table data-table table-striped table-bordered">
                      <thead>
                        <tr>
                          <th>Order ID</th>
                          <th>Date</th>
                          <th>Line Items</th>
                          <th>Amount</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($orders as $o)
                          <tr>
                            <td>{{ $o->order_id }}</td>
                            <td>{{ $o->date_added ? \Carbon\Carbon::parse($o->date_added)->format('d-m-Y') : '-' }}</td>
                            <td>{{ $o->line_items }}</td>
                            <td>{{ $o->total_amount }}</td>
                          </tr>
                        @empty
                          <tr><td colspan="4" class="text-center">No delivered orders in range.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>
                  <div class="mt-3">{{ $orders->links() }}</div>
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
