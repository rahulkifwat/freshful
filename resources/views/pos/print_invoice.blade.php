<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice #{{ $order->order_id }} | Freshful</title>
    <link rel="stylesheet" href="{{ url('assets/pos/css/bootstrap.min.css') }}">
    <style>
        body { background: #fff; font-family: Arial, sans-serif; font-size: 13px; }
        .invoice-box { max-width: 700px; margin: 20px auto; padding: 20px; border: 1px solid #eee; }
        .invoice-header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 6px 8px; border: 1px solid #ddd; }
        table thead { background: #333; color: #fff; }
        tfoot td { font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer-note { border-top: 1px solid #ccc; padding-top: 10px; margin-top: 20px; text-align: center; color: #777; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">
<div class="invoice-box">
    <div class="invoice-header">
        <table style="border:none">
            <tr>
                <td style="border:none; width:50%">
                    <img src="{{ asset('assets/image/logo.png') }}" height="50" alt="Freshful"><br>
                    <small style="color:#777">Freshful | support@freshful.in</small>
                </td>
                <td style="border:none; width:50%; text-align:right">
                    <h3 style="margin:0">INVOICE</h3>
                    <p style="margin:4px 0">Order #: <strong>{{ $order->order_id }}</strong></p>
                    <p style="margin:4px 0">Date: {{ \Carbon\Carbon::parse($order->date_added)->format('d-m-Y h:i a') }}</p>
                    <p style="margin:4px 0">Status: {{ $order->order_status }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table style="border:none; margin-bottom:15px">
        <tr>
            <td style="border:none; width:50%; vertical-align:top">
                <strong>Bill To:</strong><br>
                {{ $buyer->name ?? $order->name }}<br>
                {{ $buyer->phone ?? $order->phone }}<br>
                {{ $buyer->email ?? '' }}
            </td>
            <td style="border:none; width:50%; vertical-align:top">
                <strong>Delivery Address:</strong><br>
                @if($address)
                {{ $address->address_line1 ?? '' }}<br>
                {{ $address->address_line2 ?? '' }}<br>
                {{ $address->city ?? '' }} {{ $address->pincode ?? '' }}
                @else
                —
                @endif
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
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
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">₹{{ number_format($item->unit_price ?? $item->price, 2) }}</td>
                <td class="text-right">₹{{ number_format($item->total_price ?? ($item->quantity * $item->price), 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            @if(isset($order->discount_amount) && $order->discount_amount > 0)
            <tr>
                <td colspan="4" class="text-right">Discount</td>
                <td class="text-right" style="color:green">-₹{{ number_format($order->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if(isset($promo) && $promo)
            <tr>
                <td colspan="4" class="text-right">Promo ({{ $promo->promo_code }})</td>
                <td class="text-right" style="color:#17a2b8">-₹{{ number_format($order->promo_discount ?? 0, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="4" class="text-right">Grand Total</td>
                <td class="text-right">₹{{ number_format($order->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td colspan="4" class="text-right">Payment Method</td>
                <td class="text-right">{{ $order->payment_method ?? 'COD' }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer-note">
        <p>Thank you for shopping with Freshful!</p>
    </div>
</div>
<div class="no-print text-center mt-3">
    <button onclick="window.print()" class="btn btn-primary">Print</button>
    <button onclick="window.close()" class="btn btn-secondary ml-2">Close</button>
</div>
</body>
</html>
