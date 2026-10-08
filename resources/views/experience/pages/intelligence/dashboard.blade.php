@extends('layouts.app')
@section('title', 'Tourism Intelligence — KICC')
@section('content')
<div class="pt-24 max-w-7xl mx-auto px-5 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-black text-gray-900">Tourism Intelligence</h1>
        <div class="flex gap-2">
            @foreach(['week','month','quarter','year'] as $p)
            <a href="{{ route('intelligence.dashboard', ['period' => $p]) }}" class="px-3 py-1.5 rounded-full text-[10px] font-bold {{ $period === $p ? 'bg-[#0B0B0B] text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">{{ ucfirst($p) }}</a>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $totalOrders }}</div><div class="text-xs text-gray-400 mt-1">Orders ({{ $period }})</div><div class="text-xs text-emerald-600">+{{ $orderGrowth }} this week</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-[#0B0B0B]">KES {{ number_format($totalRevenue) }}</div><div class="text-xs text-gray-400 mt-1">Revenue</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $totalHotels }}</div><div class="text-xs text-gray-400 mt-1">Hotels</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $totalAttractions }}</div><div class="text-xs text-gray-400 mt-1">Attractions</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $products }}</div><div class="text-xs text-gray-400 mt-1">Marketplace Products</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $agents }}</div><div class="text-xs text-gray-400 mt-1">Verified Agents</div>@if($pendingAgents>0)<div class="text-xs text-amber-600">{{ $pendingAgents }} pending</div>@endif</div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">{{ $totalReviews }}</div><div class="text-xs text-gray-400 mt-1">Reviews ·  {{ number_format($avgRating ?? 0, 1) }} avg</div></div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5"><div class="text-2xl font-black text-gray-900">KES {{ number_format($commissions) }}</div><div class="text-xs text-gray-400 mt-1">Commissions · KES {{ number_format($pendingCommissions) }} pending</div></div>
    </div>

    @if($dailyTrend->isNotEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-6 mb-6">
        <h3 class="font-bold text-gray-900 text-sm mb-4">Daily Trend ({{ $period }})</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-gray-100 text-xs text-gray-400"><th class="pb-2 text-left">Date</th><th class="pb-2 text-right">Orders</th><th class="pb-2 text-right">Revenue</th></tr></thead>
                <tbody>@foreach($dailyTrend as $d)<tr class="border-b border-gray-50"><td class="py-2 text-gray-600">{{ $d->date }}</td><td class="py-2 text-right font-semibold">{{ $d->count }}</td><td class="py-2 text-right font-semibold">KES {{ number_format($d->revenue ?? 0) }}</td></tr>@endforeach</tbody>
            </table>
        </div>
    </div>
    @endif

    @if($topCounties->isNotEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-6">
        <h3 class="font-bold text-gray-900 text-sm mb-4">Top Counties by Products</h3>
        <div class="space-y-2">@foreach($topCounties as $i => $c)<div class="flex items-center justify-between text-sm"><span class="text-gray-600">{{ $i+1 }}. {{ $c->name }}</span><span class="font-semibold text-gray-900">{{ $c->products_count }} products</span></div>@endforeach</div>
    </div>
    @endif
</div>
@endsection