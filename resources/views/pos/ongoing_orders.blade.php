@extends('pos.layout.master')
@section('content')
<div class="content-page">
    <div class="content">
        <h3>Ongoing Orders</h3>
        <section id="tabs">
            <div class="container-fluid p-0">
                <nav>
                    <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                        <a class="nav-item nav-link active" data-toggle="tab" href="#sla-breach" role="tab">
                            <span class="text-danger">SLA Breach ({{ $sla_breach->count() }})</span>
                        </a>
                        <a class="nav-item nav-link" data-toggle="tab" href="#on-time" role="tab">
                            <span class="text-success">On-Time ({{ $on_time->count() }})</span>
                        </a>
                    </div>
                </nav>
                <div class="tab-content mt-3" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="sla-breach" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped" id="sla_breach_table">
                                <thead><tr>
                                    <th>Date</th><th>Order Id</th><th>Hub</th>
                                    <th>Customer</th><th>Mobile</th><th>Amount</th><th>Status</th><th>Action</th>
                                </tr></thead>
                                <tbody>
                                    @forelse($sla_breach as $v)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($v->date_added)->format('d-m-Y h:i a') }}</td>
                                        <td>{{ $v->order_id }}</td>
                                        <td>{{ $v->hub }}</td>
                                        <td>{{ $v->name }}</td>
                                        <td>{{ $v->phone }}</td>
                                        <td>₹{{ $v->total_amount }}</td>
                                        <td>{{ $v->order_status }}</td>
                                        <td><a href="{{ route('pos.processing_order_view', $v->order_id) }}" class="btn btn-warning btn-sm" target="_blank">View</a></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="8" class="text-center">No SLA breach orders</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="on-time" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped" id="on_time_table">
                                <thead><tr>
                                    <th>Date</th><th>Order Id</th><th>Hub</th>
                                    <th>Customer</th><th>Mobile</th><th>Amount</th><th>Status</th><th>Action</th>
                                </tr></thead>
                                <tbody>
                                    @forelse($on_time as $v)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($v->date_added)->format('d-m-Y h:i a') }}</td>
                                        <td>{{ $v->order_id }}</td>
                                        <td>{{ $v->hub }}</td>
                                        <td>{{ $v->name }}</td>
                                        <td>{{ $v->phone }}</td>
                                        <td>₹{{ $v->total_amount }}</td>
                                        <td>{{ $v->order_status }}</td>
                                        <td><a href="{{ route('pos.processing_order_view', $v->order_id) }}" class="btn btn-primary btn-sm" target="_blank">View</a></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="8" class="text-center">No on-time orders</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
