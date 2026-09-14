@extends('layouts.admin')
@section('title', 'Ecommerce Dashboard')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Ecommerce Dashboard</h1>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-6"><p class="text-gray-500 text-sm">Products</p><p class="text-3xl font-bold">{{ $stats['products_total'] }}</p><p class="text-green-600 text-sm">{{ $stats['products_active'] }} active</p></div>
        <div class="bg-white rounded-lg shadow p-6"><p class="text-gray-500 text-sm">Orders</p><p class="text-3xl font-bold">{{ $stats['orders_total'] }}</p><p class="text-yellow-600 text-sm">{{ $stats['orders_pending'] }} pending</p></div>
        <div class="bg-white rounded-lg shadow p-6"><p class="text-gray-500 text-sm">Revenue (Total)</p><p class="text-3xl font-bold">KES {{ number_format($stats['revenue_total']) }}</p><p class="text-green-600 text-sm">KES {{ number_format($stats['revenue_month']) }} this month</p></div>
        <div class="bg-white rounded-lg shadow p-6"><p class="text-gray-500 text-sm">Low Stock</p><p class="text-3xl font-bold text-red-600">{{ $stats['low_stock'] }}</p><p class="text-gray-600 text-sm">{{ $stats['counties_with_products'] }} counties</p></div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('admin.ecommerce.products') }}" class="bg-blue-50 rounded-lg p-4 hover:bg-blue-100"><p class="font-semibold">Products</p><p class="text-sm text-gray-600">Manage inventory</p></a>
        <a href="{{ route('admin.ecommerce.orders') }}" class="bg-green-50 rounded-lg p-4 hover:bg-green-100"><p class="font-semibold">Orders</p><p class="text-sm text-gray-600">{{ $stats['orders_processing'] }} processing</p></a>
        <a href="{{ route('admin.ecommerce.import.form') }}" class="bg-purple-50 rounded-lg p-4 hover:bg-purple-100"><p class="font-semibold">Import</p><p class="text-sm text-gray-600">From Amazon, eBay, Jumia</p></a>
        <a href="{{ route('admin.ecommerce.analytics') }}" class="bg-orange-50 rounded-lg p-4 hover:bg-orange-100"><p class="font-semibold">Analytics</p><p class="text-sm text-gray-600">Reports & trends</p></a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b"><h2 class="font-semibold">Recent Orders</h2></div>
            <div class="divide-y">
                @forelse($recentOrders as $order)
                <div class="p-4 flex justify-between">
                    <div><p class="font-medium">{{ $order->order_number }}</p><p class="text-sm text-gray-500">{{ $order->user?->name ?? 'Guest' }}</p></div>
                    <div class="text-right"><p class="font-medium">KES {{ number_format($order->grand_total) }}</p>
                        <span class="text-xs px-2 py-1 rounded @switch($order->fulfillment_status ?? 'pending') @case('delivered') bg-green-100 text-green-800 @case('cancelled') bg-red-100 text-red-800 @default bg-yellow-100 text-yellow-800 @endswitch">{{ $order->fulfillment_status ?? 'pending' }}</span>
                    </div>
                </div>
                @empty <div class="p-4 text-gray-500">No orders yet</div> @endforelse
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b"><h2 class="font-semibold">Top Counties</h2></div>
            <div class="divide-y">
                @forelse($salesByCounty as $s)
                <div class="p-4 flex justify-between">
                    <span>{{ $s->county?->name ?? 'Unknown' }}</span>
                    <span class="font-medium">{{ $s->total }} products</span>
                </div>
                @empty <div class="p-4 text-gray-500">No data</div> @endforelse
            </div>
        </div>
    </div>
</div>
@endsection