@extends('layouts.admin')
@section('title', "Order {$order->order_number}")
@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Order #{{ $order->order_number }}</h1>
        <a href="{{ route('admin.ecommerce.orders') }}" class="text-blue-600">&larr; Back</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-2">Customer</h3>
            <p>{{ $order->user?->name ?? 'Guest' }}</p>
            <p class="text-sm text-gray-500">{{ $order->user?->email }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-2">Payment</h3>
            <p class="text-lg font-bold">KES {{ number_format($order->grand_total) }}</p>
            <span class="text-xs px-2 py-1 rounded @if($order->payment_status === 'paid') bg-green-100 text-green-800 @else bg-yellow-100 text-yellow-800 @endif">{{ $order->payment_status }}</span>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-2">Fulfillment</h3>
            <span class="text-xs px-2 py-1 rounded @switch($order->fulfillment_status ?? 'unfulfilled') @case('delivered') bg-green-100 text-green-800 @case('cancelled') bg-red-100 text-red-800 @default bg-yellow-100 text-yellow-800 @endswitch">{{ $order->fulfillment_status ?? 'pending' }}</span>
            @if($order->tracking_number) <p class="text-sm mt-2">Tracking: {{ $order->tracking_number }}</p> @endif
        </div>
    </div>

    <form method="POST" action="{{ route('admin.ecommerce.order.status', $order->id) }}" class="bg-white rounded-lg shadow p-6 mb-8">
        @csrf
        <h3 class="font-semibold mb-4">Update Order Status</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Payment Status</label>
                <select name="payment_status" class="border rounded w-full px-3 py-2">
                    <option value="">Keep current ({{ $order->payment_status }})</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Fulfillment Status</label>
                <select name="fulfillment_status" class="border rounded w-full px-3 py-2">
                    <option value="">Keep current ({{ $order->fulfillment_status ?? 'unfulfilled' }})</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Tracking Number</label>
                <input name="tracking_number" class="border rounded w-full px-3 py-2" value="{{ $order->tracking_number }}">
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium mb-1">Notes</label>
            <textarea name="notes" rows="2" class="border rounded w-full px-3 py-2"></textarea>
        </div>
        <button class="mt-4 bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Update Order</button>
    </form>

    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b"><h3 class="font-semibold">Order Items</h3></div>
        <table class="w-full">
            <thead class="bg-gray-50"><tr><th class="p-2 text-left">Product</th><th class="p-2">Variant</th><th class="p-2">Qty</th><th class="p-2">Price</th><th class="p-2">Total</th></tr></thead>
            <tbody class="divide-y">
                @foreach($order->items as $item)
                <tr><td class="p-2">{{ $item->product?->name ?? '—' }}</td><td class="p-2 text-center">{{ $item->variant?->name ?? '—' }}</td><td class="p-2 text-center">{{ $item->quantity }}</td><td class="p-2 text-center">KES {{ number_format($item->unit_price) }}</td><td class="p-2 text-center">KES {{ number_format($item->total) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot><tr><td colspan="4" class="p-2 text-right font-semibold">Total:</td><td class="p-2 font-bold">KES {{ number_format($order->grand_total) }}</td></tr></tfoot>
        </table>
    </div>

    @if($order->statusHistory->isNotEmpty())
    <div class="bg-white rounded-lg shadow mt-6">
        <div class="p-4 border-b"><h3 class="font-semibold">Status History</h3></div>
        <div class="divide-y">
            @foreach($order->statusHistory as $h)
            <div class="p-3 flex justify-between">
                <div><p class="text-sm">{{ $h->status_from ?? '—' }} → <strong>{{ $h->status_to }}</strong></p><p class="text-xs text-gray-500">{{ $h->notes }}</p></div>
                <p class="text-xs text-gray-500">{{ $h->created_at->diffForHumans() }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection