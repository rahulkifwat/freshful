@extends('hub.layout.master')
@section('content')
<div class="right-panel">
    <div class="content">
        <div class="container-fluid">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <h3 class="page-title">Hub Transactions</h3>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('hub.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Hub Transactions</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Inward / Outward Transfer Log</h4></div>
                <div class="card-body">
                    @if(($rows instanceof \Illuminate\Support\Collection && $rows->isEmpty()) || (method_exists($rows, 'total') && $rows->total() == 0))
                        <div class="alert alert-info">No transactions found.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        @foreach(array_keys((array) $rows->first()) as $col)
                                            <th>{{ ucwords(str_replace('_', ' ', $col)) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($rows as $row)
                                    <tr>
                                        @foreach((array) $row as $val)
                                            <td>{{ $val ?? '—' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(method_exists($rows, 'links'))
                            {{ $rows->appends(request()->query())->links() }}
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
