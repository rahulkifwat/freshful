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
									<h4 class="content-title">Filter</h4>
								</div>
								<!-- /.content-head -->

								<div class="content-details show">
									<div id="pay-invoice" class="card pay-invoice">
										<div class="card-body">
											<form class="form" method="get" action="" enctype="multipart/form-data">
												<div class="pb-4">
													<a id="today" data-date_from="2026-02-04" data-date_to="2026-02-04" href="#">Today</a>&nbsp;&nbsp;
													<a id="yesterday" data-date_from="2026-02-03" data-date_to="2026-02-03" href="#">Yesterday</a>&nbsp;&nbsp;
													<a id="current_week" data-date_from="2026-02-02" data-date_to="2026-02-08" href="#">Current Week</a>&nbsp;&nbsp;
													<a id="previous_week" data-date_from="2026-01-26" data-date_to="2026-02-01" href="#">Previous Week</a>&nbsp;&nbsp;
													<a id="current_month" data-date_from="2026-02-01" data-date_to="2026-02-28" href="#">Current Month</a>&nbsp;&nbsp;
													<a id="previous_month" data-date_from="2026-01-01" data-date_to="2026-01-31" href="#">Previous Month</a>&nbsp;&nbsp;
													<a id="current_year" data-date_from="2026-01-01" data-date_to="2026-12-31" href="#">Current Year</a>&nbsp;&nbsp;
													<a id="previous_year" data-date_from="2025-01-01" data-date_to="2025-12-31" href="#">Previous Year</a>&nbsp;&nbsp;
												</div>

												<div class="row p-15">
													<div class="col-md-3">
														<div class="form-group">
															<label for="example-date-input" class="">Date From</label> <!--class="col-form-label"-->
															<div class="">
																<input class="form-control" type="date" id="date_from" name="date_from" value="" id="example-date-input" />
															</div>
														</div>
													</div>
													<div class="col-md-3">
														<div class="form-group">
															<label for="example-date-input" class="">Date To</label>
															<div class="">
																<input class="form-control" type="date" id="date_to" name="date_to" value="" id="example-date-input" />
															</div>
														</div>
													</div>
													<div class="col-md-3">
														<div class="form-group">
															<label class="">City</label>
															<select id="city_id" name="city" class="form-control ">
																<option value="">Select City</option>
																<option value="1">Mumbai</option>
																<option value="2">New Delhi</option>
																<option value="3">Ambur</option>
																<option value="4">Vaniyambadi</option>
																<option value="5">Bengaluru</option>
																<option value="6">Ambaji</option>
																<option value="7">Chandigarh</option>
																<option value="8">Vadodara</option>
																<option value="9">Vellore</option>
																<option value="10">Bhopal</option>
																<option value="13">Thanjavur</option>
																<option value="14">Indore</option>
															</select>
														</div>
													</div>
													<div class="col-md-3">
														<div class="form-group">
															<label class="">Hub</label>
															<select id="hub" name="hub" class="form-control ">
															</select>
														</div>
													</div>
													<!--</div>
                            <div class="row p-15">-->
													<div class="col-md-3">
														<div class="form-group">
															<label for="example-date-input" class="">Customer Name</label>
															<div class="">
																<input class="form-control" type="text" name="customer" placeholder="Enter customer name" value="" />
															</div>
														</div>
													</div>
													<div class="col-md-3">
														<div class="form-group">
															<label for="example-date-input" class="">Customer Mobile</label>
															<div class="">
																<input class="form-control" type="text" name="customer_mobile" maxlength="10" placeholder="Enter customer mobile" value="" />
															</div>
														</div>
													</div>
													<div class="col-md-3">
														<div class="form-group">
															<label>Status</label>
															<select name="order_status" class="form-control">
																<option value="">Select</option>
																<option value="Order Pending">Order Pending</option>
																<option value="Order Cancel">Order Cancel</option>
																<option value="Order Placed">Order Placed</option>
																<option value="Order Processed">Order Processed</option>
																<option value="Order Shipped">Order Shipped</option>
																<option value="Order Delivered">Order Delivered</option>
															</select>
														</div>
													</div>
													<div class="col-md-3">
														<div class="form-group">
															<label>Delivery Type</label>
															<select name="delivery_type" class="form-control ">
																<option value="">Select Delivery Type</option>
																<option value="Express">Express</option>
																<option value="Scheduled">Scheduled</option>
															</select>
														</div>
													</div>
												</div>
												<div class="row p-15">

													<div class="col-12 mt-25 text-center">
														<a href="all_orders.php" class="btn btn-rounded btn-warning btn-outline mr-1">
															Reset
														</a>
														<input type="submit" class="btn btn-rounded btn-primary btn-outline" />
													</div>
												</div>
											</form>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-12">
							<div class="section-content">
								<div class="content-head">
									<h4 class="content-title">All Orders</h4>

								</div>

								<div class="content-details show">
									<div class="order-content">

										<form class="form-horizontal"  method="post">


											<div class="row" >
												<div class="col-md-3" style="margin-bottom: 10px;">
													<input type="date" name="date" class="form-control" value="">

												</div>
												<div class="col-md-3" style="margin-bottom: 10px;">
													<button type="submit" class="btn btn-primary">Search</button>
												</div>


											</div>
										</form>

										<div class="table-responsive">
											<table id="data-table" class="table data-table table-striped table-bordered">
												<thead>
													<tr>
														<th>S.No.</th>
														<th>Date</th>
														<th>Order Id</th>
														<th>Delivery Type</th>
														<th>Hub</th>
														<th>Customer Name</th>
														<th>Customer Mobile</th>
														<th>Status</th>
														<th>Amount</th>
														<th>Action</th>
													</tr>
												</thead>
												<tbody>
													@foreach($orders as $key => $v)
													<tr>
														<td>{{ $key + 1 }}</td>
														<td>{{ \Carbon\Carbon::parse($v->date_added)->format('d-m-Y') }}</td>
														<td>{{ $v->order_id }}</td>
														<td>{{ $v->delivery_type }}</td>
														<td>{{ $v->hub }}</td>
														<td>{{ $v->name }}</td>
														<td>{{ $v->phone }}</td>

														<td>
															<span class="badge
																@if($v->order_status == 'Order Pending') badge-secondary
																@elseif($v->order_status == 'Order Cancel') badge-danger
																@elseif($v->order_status == 'Order Placed') badge-info
																@elseif($v->order_status == 'Order Processed') badge-warning
																@elseif($v->order_status == 'Order Shipped') badge-primary
																@else badge-success
																@endif
															">
																{{ $v->order_status }}
															</span>
														</td>

														<td>{{ $v->total_amount }}</td>

														<td>
															<a href="{{ url('admin/view_operation_order?order_id='.$v->order_id) }}"
															class="btn btn-primary btn-sm">
															View
															</a>
														</td>
													</tr>
													@endforeach
												</tbody>
											</table>
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