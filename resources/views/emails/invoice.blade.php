<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Invoice {{ $order->order_number }}</title>
<style>body{font-family:sans-serif;max-width:600px;margin:0 auto;padding:20px}
table{width:100%;border-collapse:collapse;margin:20px 0}
th,td{padding:8px 12px;border-bottom:1px solid #ddd;text-align:left}
.total{font-weight:bold;font-size:1.2em}</style></head>
<body>
<h1>KICC Marketplace Invoice</h1>
<p><strong>Order:</strong> {{ $order->order_number }}<br>
<strong>Date:</strong> {{ $order->created_at->format('d M Y') }}<br>
<strong>Customer:</strong> {{ $order->user?->name ?? 'Guest' }}<br>
<strong>Email:</strong> {{ $order->user?->email ?? 'N/A' }}</p>
<h3>Items</h3>
<table><thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
<tbody>
@foreach($order->items as $item)
<tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>KES {{ number_format($item->unit_price) }}</td><td>KES {{ number_format($item->total) }}</td></tr>
@endforeach
</tbody></table>
<div class="total">Grand Total: KES {{ number_format($order->grand_total) }}</div>
<p>Thank you for shopping at KICC Marketplace!</p>
</body></html>