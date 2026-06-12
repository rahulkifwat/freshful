@extends('pos.layout.master')
@section('content')
<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-9">
                    <div class="card" id="invoice_card">
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <img src="{{ asset('assets/image/logo.png') }}" alt="Logo" height="50"><br>
                                    <p class="text-muted mt-2 mb-0">Freshful<br>Customer Support: support@freshful.in</p>
                                </div>
                                <div class="col-sm-6 text-right">
                                    <h4>Invoice</h4>
                                    <p class="mb-1"><strong>Order #:</strong> {{ $order->order_id }}</p>
                                    <p class="mb-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($order->date_added)->format('d-m-Y h:i a') }}</p>
                                    <p class="mb-1"><strong>Status:</strong> {{ $order->order_status }}</p>
                                    <p class="mb-0"><strong>Payment:</strong> {{ $order->payment_method ?? 'COD' }}</p>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <h6>Bill To:</h6>
                                    <p class="mb-1"><strong>{{ $buyer->name ?? $order->name }}</strong></p>
                                    <p class="mb-1">{{ $buyer->phone ?? $order->phone }}</p>
                                    <p class="mb-1">{{ $buyer->email ?? '' }}</p>
                                </div>
                                <div class="col-sm-6">
                                    <h6>Delivery Address:</h6>
                                    @if($address)
                                    <p class="mb-1">{{ $address->address_line1 ?? '' }}</p>
                                    <p class="mb-1">{{ $address->address_line2 ?? '' }}</p>
                                    <p class="mb-1">{{ $address->city ?? '' }} {{ $address->pincode ?? '' }}</p>
                                    @else
                                    <p class="text-muted">—</p>
                                    @endif
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>#</th><th>Product</th><th>SKU</th>
                                            <th class="text-right">Qty</th>
                                            <th class="text-right">Unit Price</th>
                                            <th class="text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $i => $item)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $item->product_name ?? $item->name }}</td>
                                            <td>{{ $item->sku ?? '-' }}</td>
                                            <td class="text-right">{{ $item->quantity }}</td>
                                            <td class="text-right">₹{{ number_format($item->unit_price ?? $item->price, 2) }}</td>
                                            <td class="text-right">₹{{ number_format($item->total_price ?? ($item->quantity * $item->price), 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        @if(isset($order->discount_amount) && $order->discount_amount > 0)
                                        <tr>
                                            <td colspan="5" class="text-right">Discount</td>
                                            <td class="text-right text-success">-₹{{ number_format($order->discount_amount, 2) }}</td>
                                        </tr>
                                        @endif
                                        @if(isset($promo) && $promo)
                                        <tr>
                                            <td colspan="5" class="text-right">Promo ({{ $promo->promo_code }})</td>
                                            <td class="text-right text-info">-₹{{ number_format($order->promo_discount ?? 0, 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td colspan="5" class="text-right"><strong>Grand Total</strong></td>
                                            <td class="text-right"><strong>₹{{ number_format($order->total_amount, 2) }}</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12 text-center text-muted">
                                    <p>Thank you for shopping with Freshful! For queries, contact support@freshful.in</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-3 no-print">
                        <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
                        <a href="javascript:history.back()" class="btn btn-secondary ml-2">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('styles')
<style>
@media print {
    .no-print, .left-side-menu, .topbar { display: none !important; }
    .content-page { margin: 0 !important; padding: 0 !important; }
}
</style>
@endpush
@endsection
