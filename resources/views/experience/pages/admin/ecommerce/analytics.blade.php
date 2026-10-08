@extends('layouts.admin')
@section('title', 'Analytics')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Ecommerce Analytics</h1>

    <div class="flex gap-4 mb-6">
        <a href="?period=day" class="px-3 py-1 rounded @if($period === 'day') bg-blue-600 text-white @else bg-gray-200 @endif">Daily</a>
        <a href="?period=week" class="px-3 py-1 rounded @if($period === 'week') bg-blue-600 text-white @else bg-gray-200 @endif">Weekly</a>
        <a href="?period=month" class="px-3 py-1 rounded @if($period === 'month') bg-blue-600 text-white @else bg-gray-200 @endif">Monthly</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-4">Order Status Breakdown</h3>
            <div class="space-y-3">
                @foreach(['pending_payment' => 'Pending Payment', 'paid' => 'Paid/Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $k => $l)
                <div class="flex justify-between"><span>{{ $l }}</span><span class="font-medium">{{ $statusBreakdown[$k] ?? 0 }}</span></div>
                @endforeach
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-4">Revenue by County</h3>
            <div class="space-y-2">
                @forelse($ordersByCounty as $o)
                <div class="flex justify-between"><span>{{ $o->county }}</span><span class="font-medium">KES {{ number_format($o->revenue) }} ({{ $o->orders }} orders)</span></div>
                @empty <p class="text-gray-500">No data</p> @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="font-semibold mb-4">Top Selling Products</h3>
        <table class="w-full">
            <thead><tr class="text-left"><th class="p-2">Product</th><th class="p-2">Units Sold</th><th class="p-2">Revenue</th></tr></thead>
            <tbody class="divide-y">
                @forelse($topProducts as $p)
                <tr><td class="p-2">{{ $p->name }}</td><td class="p-2">{{ $p->sold_count }}</td><td class="p-2">KES {{ number_format($p->revenue) }}</td></tr>
                @empty <tr><td colspan="3" class="p-4 text-center text-gray-500">No sales data</td></tr> @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold mb-4">Revenue Trend</h3>
        <div class="space-y-2">
            @forelse($revenue as $r)
            <div class="flex justify-between"><span>{{ $r->label }}</span><span class="font-medium">KES {{ number_format($r->revenue) }} ({{ $r->orders }} orders)</span></div>
            @empty <p class="text-gray-500">No revenue data yet</p> @endforelse
        </div>
    </div>
</div>
@endsection