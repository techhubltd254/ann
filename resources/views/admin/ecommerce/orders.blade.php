@extends('layouts.admin')
@section('title', 'Orders')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Order Management</h1>

    <form class="flex gap-2 mb-4" method="GET">
        <input name="q" placeholder="Order # or customer..." value="{{ request('q') }}" class="border rounded px-3 py-1 w-64">
        <select name="status" class="border rounded px-3 py-1">
            <option value="">All Status</option>
            <option value="pending_payment" @selected(request('status') === 'pending_payment')>Pending Payment</option>
            <option value="paid" @selected(request('status') === 'paid')>Paid / Processing</option>
            <option value="delivered" @selected(request('status') === 'delivered')>Delivered</option>
            <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
        </select>
        <button class="bg-gray-200 px-4 py-1 rounded">Filter</button>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50"><tr><th class="p-3 text-left">Order #</th><th class="p-3 text-left">Customer</th><th class="p-3">Items</th><th class="p-3">Total</th><th class="p-3">Payment</th><th class="p-3">Fulfillment</th><th class="p-3">Date</th><th class="p-3"></th></tr></thead>
            <tbody class="divide-y">
                @forelse($orders as $o)
                <tr>
                    <td class="p-3 font-medium">{{ $o->order_number }}</td>
                    <td class="p-3">{{ $o->user?->name ?? 'Guest' }}<br><span class="text-xs text-gray-500">{{ $o->user?->email }}</span></td>
                    <td class="p-3 text-center">{{ $o->items->sum('quantity') }}</td>
                    <td class="p-3">KES {{ number_format($o->grand_total) }}</td>
                    <td class="p-3"><span class="text-xs px-2 py-1 rounded @if($o->payment_status === 'paid') bg-green-100 text-green-800 @else bg-yellow-100 text-yellow-800 @endif">{{ $o->payment_status }}</span></td>
                    <td class="p-3"><span class="text-xs px-2 py-1 rounded @switch($o->fulfillment_status ?? 'unfulfilled') @case('delivered') bg-green-100 text-green-800 @case('cancelled') bg-red-100 text-red-800 @case('shipped') bg-blue-100 text-blue-800 @default bg-yellow-100 text-yellow-800 @endswitch">{{ $o->fulfillment_status ?? 'pending' }}</span></td>
                    <td class="p-3 text-sm text-gray-500">{{ $o->created_at->format('d M Y') }}</td>
                    <td class="p-3"><a href="{{ route('admin.ecommerce.order.show', $o->id) }}" class="text-blue-600 text-sm hover:underline">Manage</a></td>
                </tr>
                @empty <tr><td colspan="8" class="p-4 text-center text-gray-500">No orders</td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</div>
@endsection