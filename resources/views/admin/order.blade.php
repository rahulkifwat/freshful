@extends('admin.layout.master')

@section('content')



<div class="dashboard-contents">
  <div class="contents-inner">
    <div class="row">

      <div class="full-wdt">
        <div class="contents-inner">
          <div class="row">
            <div class="col-md-12">
              <div class="section-content">
                <div class="content-head">
                  <h4 class="content-title">Orders</h4><!--.content-title-->
                </div><!--.content-head -->

                <div class="content-details show">
                  <div class="order-content">

                    <div class="col-md-6" style="margin-bottom: 10px;">
                    </div>

                    <div id="order-listing_wrapper" class="dataTables_wrapper no-footer">
                      <div class="dataTables_length" id="order-listing_length"><label>Show <select name="order-listing_length" aria-controls="order-listing" class="">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                          </select> entries</label></div>
                      <div id="order-listing_filter" class="dataTables_filter"><label>Search:<input type="search" class="" placeholder="" aria-controls="order-listing"></label></div>
                      <table id="order-listing" class="table table-striped dataTable no-footer" role="grid" aria-describedby="order-listing_info">
                        <thead>
                          <tr role="row">
                            <th class="sorting_asc" tabindex="0" aria-controls="order-listing" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Order #: activate to sort column descending" style="width: 98.1534px;">Order #</th>
                            <th class="sorting" tabindex="0" aria-controls="order-listing" rowspan="1" colspan="1" aria-label="Purchased On: activate to sort column ascending" style="width: 164.896px;">Purchased On</th>
                            <th class="sorting" tabindex="0" aria-controls="order-listing" rowspan="1" colspan="1" aria-label="Customer: activate to sort column ascending" style="width: 113.428px;">Customer</th>
                            <th class="sorting" tabindex="0" aria-controls="order-listing" rowspan="1" colspan="1" aria-label="Price: activate to sort column ascending" style="width: 105.54px;">Price</th>
                            <th class="sorting" tabindex="0" aria-controls="order-listing" rowspan="1" colspan="1" aria-label="Order: activate to sort column ascending" style="width: 187.737px;">Order</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($orders as $order)
                          <tr>
                              <td>{{ $order->order_id }}</td>
                              <td>{{ $order->date_added }}</td>
                              <td>{{ $order->customer_name }}</td>
                              <td>{{ $order->total_amount }}</td>

                              <td>
                                  <select class="change_status"
                                      data-id="{{ $order->id }}"
                                      data-order_id="{{ $order->order_id }}">

                                      @foreach([
                                          'Order Pending',
                                          'Order Cancel',
                                          'Order Placed',
                                          'Order Processed',
                                          'Order Shipped',
                                          'Order Delivered'
                                      ] as $status)

                                          <option value="{{ $status }}"
                                              {{ $order->order_status == $status ? 'selected' : '' }}>
                                              {{ $status }}
                                          </option>

                                      @endforeach
                                  </select>
                              </td>
                          </tr>
                          @endforeach
                        </tbody>
                      </table>
                      {{ $orders->links() }}
                      {{-- <div class="dataTables_info" id="order-listing_info" role="status" aria-live="polite">Showing 1 to 10 of 26 entries</div>
                      <div class="dataTables_paginate paging_simple_numbers" id="order-listing_paginate"><a class="paginate_button previous disabled" aria-controls="order-listing" data-dt-idx="0" tabindex="0" id="order-listing_previous">Previous</a><span><a class="paginate_button current" aria-controls="order-listing" data-dt-idx="1" tabindex="0">1</a><a class="paginate_button " aria-controls="order-listing" data-dt-idx="2" tabindex="0">2</a><a class="paginate_button " aria-controls="order-listing" data-dt-idx="3" tabindex="0">3</a></span><a class="paginate_button next" aria-controls="order-listing" data-dt-idx="4" tabindex="0" id="order-listing_next">Next</a></div> --}}
                    </div>
                  </div><!-- /.order-content -->
                </div><!-- /.content-details -->
              </div>
            </div>
          </div>
        </div><!-- /.contents-inner -->
      </div>

    </div>
  </div><!-- /.contents-inner -->



</div><!-- /.dashboard-contents -->














@endsection