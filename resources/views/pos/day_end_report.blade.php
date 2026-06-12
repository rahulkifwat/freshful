@extends('pos.layout.master')
@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <h3>Day End Report</h3>

            <form method="GET" class="form-inline mb-4">
                <label class="mr-2">From:</label>
                <input type="date" name="date_from" class="form-control form-control-sm mr-2" value="{{ request('date_from', date('Y-m-d')) }}" required>
                <label class="mr-2">To:</label>
                <input type="date" name="date_to" class="form-control form-control-sm mr-2" value="{{ request('date_to', date('Y-m-d')) }}" required>
                <button type="submit" class="btn btn-primary btn-sm mr-1">Generate Report</button>
                <a href="{{ route('pos.day_end_report') }}" class="btn btn-secondary btn-sm">Reset</a>
            </form>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Express Orders</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th>Opening Orders</th>
                                        <td class="text-right">{{ $express['opening'] ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <th>New Orders Received</th>
                                        <td class="text-right">{{ $express['received'] ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <th>Delivered</th>
                                        <td class="text-right text-success"><strong>{{ $express['delivered'] ?? 0 }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Pending</th>
                                        <td class="text-right text-warning"><strong>{{ $express['pending'] ?? 0 }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Rejected / Cancelled</th>
                                        <td class="text-right text-danger"><strong>{{ $express['rejected'] ?? 0 }}</strong></td>
                                    </tr>
                                    <tr class="table-active">
                                        <th>Total Revenue</th>
                                        <td class="text-right"><strong>₹{{ number_format($express['revenue'] ?? 0, 2) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Scheduled Orders</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <tr>
                                        <th>Opening Orders</th>
                                        <td class="text-right">{{ $scheduled['opening'] ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <th>New Orders Received</th>
                                        <td class="text-right">{{ $scheduled['received'] ?? 0 }}</td>
                                    </tr>
                                    <tr>
                                        <th>Delivered</th>
                                        <td class="text-right text-success"><strong>{{ $scheduled['delivered'] ?? 0 }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Pending</th>
                                        <td class="text-right text-warning"><strong>{{ $scheduled['pending'] ?? 0 }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Rejected / Cancelled</th>
                                        <td class="text-right text-danger"><strong>{{ $scheduled['rejected'] ?? 0 }}</strong></td>
                                    </tr>
                                    <tr class="table-active">
                                        <th>Total Revenue</th>
                                        <td class="text-right"><strong>₹{{ number_format($scheduled['revenue'] ?? 0, 2) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">Combined Summary</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th></th>
                                        <th class="text-center">Express</th>
                                        <th class="text-center">Scheduled</th>
                                        <th class="text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th>Total Orders</th>
                                        <td class="text-center">{{ ($express['opening'] ?? 0) + ($express['received'] ?? 0) }}</td>
                                        <td class="text-center">{{ ($scheduled['opening'] ?? 0) + ($scheduled['received'] ?? 0) }}</td>
                                        <td class="text-center"><strong>{{ ($express['opening'] ?? 0) + ($express['received'] ?? 0) + ($scheduled['opening'] ?? 0) + ($scheduled['received'] ?? 0) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Delivered</th>
                                        <td class="text-center text-success">{{ $express['delivered'] ?? 0 }}</td>
                                        <td class="text-center text-success">{{ $scheduled['delivered'] ?? 0 }}</td>
                                        <td class="text-center text-success"><strong>{{ ($express['delivered'] ?? 0) + ($scheduled['delivered'] ?? 0) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Revenue</th>
                                        <td class="text-center">₹{{ number_format($express['revenue'] ?? 0, 2) }}</td>
                                        <td class="text-center">₹{{ number_format($scheduled['revenue'] ?? 0, 2) }}</td>
                                        <td class="text-center"><strong>₹{{ number_format(($express['revenue'] ?? 0) + ($scheduled['revenue'] ?? 0), 2) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @if(request('date_from'))
            <div class="mt-3 no-print">
                <button onclick="window.print()" class="btn btn-outline-dark btn-sm">Print Report</button>
            </div>
            @endif
        </div>
    </div>
</div>
@push('styles')
<style>
@media print {
    .no-print, .left-side-menu, .topbar, form { display: none !important; }
    .content-page { margin: 0 !important; }
}
</style>
@endpush
@endsection
